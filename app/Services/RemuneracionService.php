<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\User;
use App\Support\Buscador;
use Carbon\CarbonImmutable;
use Exception;

class RemuneracionService
{
    public const ORIGEN_DIAS_LICENCIAS = 'licencias';

    public const ORIGEN_DIAS_ASISTENCIA = 'asistencia';

    public const ORIGEN_DIAS_MANUAL = 'manual';

    /**
     * Calcula y genera la liquidación de sueldo para un trabajador en un mes y año específico.
     *
     * Los datos previsionales se toman del trabajador:
     *  - afp_tasa: 10% obligatorio + comisión vigente de su AFP.
     *  - salud_plan_uf: plan de Isapre en UF (el mínimo legal es 7% del imponible).
     *
     * El SIS se calcula como aporte del empleador y NO se descuenta del líquido.
     * El impuesto único de segunda categoría se calcula sobre la renta líquida
     * imponible (total imponible menos AFP, salud y cesantía).
     *
     * Los días trabajados se pueden indicar manualmente o calcular desde las
     * licencias médicas o las asistencias registradas en el período.
     *
     * @param  array{
     *     origen_dias?: string,
     *     anticipo?: int,
     *     descuento_sindicato?: int,
     *     otros_descuentos?: int,
     *     cargas_familiares?: int,
     *     colacion?: int,
     *     movilizacion?: int
     * }  $opciones
     */
    public function calcularLiquidacion(
        Trabajador $trabajador,
        int $mes,
        int $anio,
        ?int $diasTrabajados = null,
        array $opciones = [],
    ): Liquidacion {
        $parametroLegal = $this->parametroDelPeriodo($mes, $anio);

        // Una liquidación cerrada es un documento inmutable: debe reabrirse
        // con motivo y permisos antes de volver a calcularse.
        $existente = Liquidacion::query()
            ->where('trabajador_id', $trabajador->id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if ($existente?->cerrada) {
            throw new Exception(
                "La liquidación de {$mes}/{$anio} está cerrada. Reábrela con un motivo antes de recalcularla.",
            );
        }

        $origenDias = (string) ($opciones['origen_dias'] ?? self::ORIGEN_DIAS_LICENCIAS);
        [$diasTrabajados, $origenDias] = $this->resolverDiasTrabajados(
            $trabajador,
            $mes,
            $anio,
            $diasTrabajados,
            $origenDias,
        );

        $contrato = $this->contratoVigente($trabajador, $mes, $anio);

        $sueldoBaseContrato = (int) $contrato->sueldo_base;

        // Proporción de días trabajados (mes comercial de 30 días).
        $proporcionDias = $diasTrabajados / 30;

        // 1. Haberes Imponibles
        $sueldoBaseProporcional = (int) round($sueldoBaseContrato * $proporcionDias);

        // Gratificación: 25% del sueldo base, con tope mensual de
        // (4.75 sueldos mínimos anuales / 12), prorrateado por días.
        $topeGratificacion = (4.75 * (int) $parametroLegal->sueldo_minimo) / 12;
        $gratificacion = (int) round(min(
            $sueldoBaseProporcional * 0.25,
            $topeGratificacion * $proporcionDias,
        ));

        $totalImponible = $sueldoBaseProporcional + $gratificacion;

        // Validar tope imponible AFP
        $topeImponibleAfpPesos = (int) round($parametroLegal->tope_imponible_afp * $parametroLegal->valor_uf);
        $baseCalculoAfp = min($totalImponible, $topeImponibleAfpPesos);

        // 2. Descuentos Legales
        [$afpNombre, $afpPorcentaje] = $this->datosAfp($trabajador);
        $montoAfp = (int) round($baseCalculoAfp * ($afpPorcentaje / 100));

        // La cotización de salud tiene su propio tope imponible (60 UF),
        // distinto del tope de AFP (84,3 UF).
        $topeImponibleSaludPesos = (int) round($parametroLegal->tope_imponible_ips * $parametroLegal->valor_uf);
        $baseCalculoSalud = min($totalImponible, $topeImponibleSaludPesos);

        [$saludNombre, $saludPorcentaje, $montoSalud] = $this->datosSalud(
            $trabajador,
            $parametroLegal,
            $baseCalculoSalud,
        );

        // Seguro de Cesantía (0.6% a cargo del trabajador si tiene contrato indefinido)
        $montoCesantia = 0;
        if (str_contains(Buscador::normalizar($contrato->tipo_contrato), 'indefinid')) {
            $topeImponibleCesantiaPesos = (int) round($parametroLegal->tope_imponible_cesantia * $parametroLegal->valor_uf);
            $baseCalculoCesantia = min($totalImponible, $topeImponibleCesantiaPesos);
            $montoCesantia = (int) round($baseCalculoCesantia * 0.006);
        }

        // El IUSC se aplica sobre la renta líquida imponible, después de las
        // cotizaciones previsionales del trabajador.
        $rentaLiquidaImponible = max(
            0,
            $totalImponible - $montoAfp - $montoSalud - $montoCesantia,
        );

        $impuesto = $this->calcularImpuestoSegundaCategoria(
            $rentaLiquidaImponible,
            (int) $parametroLegal->valor_utm,
        );

        $totalDescuentosLegales = $montoAfp + $montoSalud + $montoCesantia + $impuesto;

        // SIS: aporte del empleador, informativo. No se descuenta del trabajador.
        $sisPorcentaje = (float) $parametroLegal->porcentaje_sis;
        $montoSis = (int) round($baseCalculoAfp * ($sisPorcentaje / 100));

        // 3. Haberes No Imponibles (asignaciones)
        $montoColacion = $this->montoOpcion($opciones, 'colacion');
        $montoMovilizacion = $this->montoOpcion($opciones, 'movilizacion');
        $cargasFamiliares = $this->montoOpcion($opciones, 'cargas_familiares');

        $totalNoImponible = $montoColacion + $montoMovilizacion + $cargasFamiliares;

        // Total Haberes
        $totalHaberes = $totalImponible + $totalNoImponible;

        // 4. Otros Descuentos (no afectos a renta)
        $descuentoAnticipo = $this->montoOpcion($opciones, 'anticipo');
        $descuentoSindicato = $this->montoOpcion($opciones, 'descuento_sindicato');
        $otrosDescuentosVarios = $this->montoOpcion($opciones, 'otros_descuentos');
        $otrosDescuentos = $descuentoAnticipo + $descuentoSindicato + $otrosDescuentosVarios;

        $totalDescuentos = $totalDescuentosLegales + $otrosDescuentos;

        if ($totalDescuentos > $totalHaberes) {
            throw new Exception(
                'Los descuentos ($'.number_format($totalDescuentos, 0, ',', '.').') superan los haberes del período ($'
                .number_format($totalHaberes, 0, ',', '.').'). Revisa anticipos y otros descuentos.',
            );
        }

        // 5. Líquido a Pagar
        $sueldoLiquido = $totalHaberes - $totalDescuentos;

        // 6. Crear o actualizar la liquidación
        $liquidacion = Liquidacion::updateOrCreate(
            [
                'trabajador_id' => $trabajador->id,
                'mes' => $mes,
                'anio' => $anio,
            ],
            [
                'contrato_id' => $contrato->id,
                'parametro_legal_id' => $parametroLegal->id,
                'valor_uf_usado' => $parametroLegal->valor_uf,
                'valor_utm_usado' => $parametroLegal->valor_utm,
                'sueldo_minimo_usado' => $parametroLegal->sueldo_minimo,
                'tope_afp_usado' => $parametroLegal->tope_imponible_afp,
                'tope_ips_usado' => $parametroLegal->tope_imponible_ips,
                'tope_cesantia_usado' => $parametroLegal->tope_imponible_cesantia,
                'dias_trabajados' => $diasTrabajados,
                'origen_dias' => $origenDias,
                'sueldo_base' => $sueldoBaseProporcional,
                'gratificacion' => $gratificacion,
                'total_imponible' => $totalImponible,
                'renta_liquida_imponible' => $rentaLiquidaImponible,
                'monto_colacion' => $montoColacion,
                'monto_movilizacion' => $montoMovilizacion,
                'cargas_familiares' => $cargasFamiliares,
                'total_no_imponible' => $totalNoImponible,
                'total_haberes' => $totalHaberes,
                'afp_nombre' => $afpNombre,
                'afp_porcentaje' => $afpPorcentaje,
                'monto_afp' => $montoAfp,
                'salud_nombre' => $saludNombre,
                'salud_porcentaje' => $saludPorcentaje,
                'monto_salud' => $montoSalud,
                'monto_cesantia' => $montoCesantia,
                'descuento_anticipo' => $descuentoAnticipo,
                'descuento_sindicato' => $descuentoSindicato,
                'sis_porcentaje' => $sisPorcentaje,
                'monto_sis' => $montoSis,
                'impuesto_segunda_categoria' => $impuesto,
                'total_descuentos_legales' => $totalDescuentosLegales,
                'otros_descuentos' => $otrosDescuentos,
                'total_descuentos' => $totalDescuentos,
                'sueldo_liquido' => $sueldoLiquido,
                'cerrada' => false,
            ]
        );

        return $liquidacion;
    }

    /**
     * Impuesto único de segunda categoría para una renta líquida imponible.
     *
     * La tabla se expresa en UTM; los límites y rebajas en pesos dependen del
     * valor de la UTM del período.
     */
    public function calcularImpuestoSegundaCategoria(int $rentaLiquidaImponible, int $valorUtm): int
    {
        if ($rentaLiquidaImponible <= 0 || $valorUtm <= 0) {
            return 0;
        }

        $baseEnUtm = $rentaLiquidaImponible / $valorUtm;

        foreach (config('legal.impuesto_segunda_categoria.tramos', []) as $tramo) {
            $hastaUtm = $tramo['hasta_utm'] ?? null;

            if ($hastaUtm !== null && $baseEnUtm > $hastaUtm) {
                continue;
            }

            $factor = (float) ($tramo['factor'] ?? 0);
            $rebajaUtm = (float) ($tramo['rebaja_utm'] ?? 0);

            if ($factor <= 0) {
                return 0;
            }

            return max(
                0,
                (int) round($rentaLiquidaImponible * $factor - $rebajaUtm * $valorUtm),
            );
        }

        return 0;
    }

    /**
     * Días trabajados sugeridos para un período. No genera la liquidación.
     */
    public function diasTrabajadosSugeridos(
        Trabajador $trabajador,
        int $mes,
        int $anio,
        string $origen = self::ORIGEN_DIAS_LICENCIAS,
    ): int {
        [$dias] = $this->resolverDiasTrabajados($trabajador, $mes, $anio, null, $origen);

        return $dias;
    }

    /**
     * Cierra una liquidación. Exige que los parámetros legales del período
     * estén verificados por una persona.
     */
    public function cerrar(Liquidacion $liquidacion, User $usuario): Liquidacion
    {
        if ($liquidacion->cerrada) {
            throw new Exception('La liquidación ya está cerrada.');
        }

        // Se exige que estén verificados los parámetros vigentes del período.
        // Comparar contra el período actual (y no solo contra la fila usada)
        // detecta tanto ediciones como reemplazos del registro de parámetros.
        $parametro = $this->parametroDelPeriodo(
            (int) $liquidacion->mes,
            (int) $liquidacion->anio,
        );

        if (! $parametro->estaVerificado()) {
            throw new Exception(
                'No se puede cerrar la liquidación: los parámetros legales del período aún no están verificados por una persona.',
            );
        }

        $diferencias = $this->diferenciasDeParametros($liquidacion, $parametro);

        if ($diferencias !== []) {
            throw new Exception(
                'Los parámetros legales del período cambiaron después de calcular la liquidación ('
                .implode(', ', $diferencias).'). Recalcula antes de cerrar.',
            );
        }

        $liquidacion->update([
            'cerrada' => true,
            'cerrada_por_id' => $usuario->id,
            'cerrada_en' => now(),
        ]);

        return $liquidacion->refresh();
    }

    /**
     * Reabre una liquidación cerrada. El motivo es obligatorio y queda
     * registrado junto con el usuario y la fecha.
     */
    public function reabrir(Liquidacion $liquidacion, User $usuario, string $motivo): Liquidacion
    {
        if (! $liquidacion->cerrada) {
            throw new Exception('La liquidación no está cerrada.');
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new Exception('Debes indicar el motivo de la reapertura.');
        }

        $liquidacion->update([
            'cerrada' => false,
            'reabierta_por_id' => $usuario->id,
            'reabierta_en' => now(),
            'motivo_reapertura' => $motivo,
        ]);

        return $liquidacion->refresh();
    }

    /**
     * Compara la copia de parámetros guardada al calcular con los valores
     * vigentes del período. Devuelve las etiquetas de los que cambiaron.
     *
     * @return list<string>
     */
    private function diferenciasDeParametros(Liquidacion $liquidacion, ParametroLegal $parametro): array
    {
        $diferencias = [];

        if ($liquidacion->valor_uf_usado !== null
            && round((float) $liquidacion->valor_uf_usado, 2) !== round((float) $parametro->valor_uf, 2)) {
            $diferencias[] = 'UF';
        }

        if ($liquidacion->valor_utm_usado !== null
            && (int) $liquidacion->valor_utm_usado !== (int) $parametro->valor_utm) {
            $diferencias[] = 'UTM';
        }

        if ($liquidacion->sueldo_minimo_usado !== null
            && (int) $liquidacion->sueldo_minimo_usado !== (int) $parametro->sueldo_minimo) {
            $diferencias[] = 'sueldo mínimo';
        }

        if ($liquidacion->tope_afp_usado !== null
            && round((float) $liquidacion->tope_afp_usado, 2) !== round((float) $parametro->tope_imponible_afp, 2)) {
            $diferencias[] = 'tope AFP';
        }

        if ($liquidacion->tope_ips_usado !== null
            && round((float) $liquidacion->tope_ips_usado, 2) !== round((float) $parametro->tope_imponible_ips, 2)) {
            $diferencias[] = 'tope salud';
        }

        if ($liquidacion->tope_cesantia_usado !== null
            && round((float) $liquidacion->tope_cesantia_usado, 2) !== round((float) $parametro->tope_imponible_cesantia, 2)) {
            $diferencias[] = 'tope cesantía';
        }

        if ($liquidacion->sis_porcentaje !== null
            && round((float) $liquidacion->sis_porcentaje, 2) !== round((float) $parametro->porcentaje_sis, 2)) {
            $diferencias[] = 'SIS';
        }

        return $diferencias;
    }

    public function parametroDelPeriodo(int $mes, int $anio): ParametroLegal
    {
        $parametroLegal = ParametroLegal::query()
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (! $parametroLegal) {
            throw new Exception("No existen parámetros legales configurados para el período $mes-$anio.");
        }

        return $parametroLegal;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function resolverDiasTrabajados(
        Trabajador $trabajador,
        int $mes,
        int $anio,
        ?int $diasTrabajados,
        string $origen,
    ): array {
        if ($diasTrabajados !== null) {
            return [$this->validarDias($diasTrabajados), self::ORIGEN_DIAS_MANUAL];
        }

        if (! in_array($origen, [
            self::ORIGEN_DIAS_LICENCIAS,
            self::ORIGEN_DIAS_ASISTENCIA,
            self::ORIGEN_DIAS_MANUAL,
        ], true)) {
            throw new Exception("El origen de días '{$origen}' no es válido.");
        }

        if ($origen === self::ORIGEN_DIAS_MANUAL) {
            throw new Exception('Debes indicar los días trabajados cuando el origen es manual.');
        }

        if ($origen === self::ORIGEN_DIAS_ASISTENCIA) {
            return [$this->diasConAsistencia($trabajador, $mes, $anio), $origen];
        }

        return [$this->validarDias(30 - $this->diasConLicencia($trabajador, $mes, $anio)), $origen];
    }

    private function validarDias(int $dias): int
    {
        if ($dias < 0 || $dias > 30) {
            throw new Exception('Los días trabajados deben estar entre 0 y 30.');
        }

        return $dias;
    }

    /**
     * Días del período cubiertos por licencias médicas no rechazadas.
     */
    private function diasConLicencia(Trabajador $trabajador, int $mes, int $anio): int
    {
        return count($this->fechasConLicencia($trabajador, $mes, $anio));
    }

    /**
     * Fechas (Y-m-d) del período cubiertas por licencias médicas no rechazadas.
     *
     * @return array<int, string>
     */
    private function fechasConLicencia(Trabajador $trabajador, int $mes, int $anio): array
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->endOfMonth();

        $licencias = $trabajador->licenciasMedicas()
            ->where('estado', '!=', 'Rechazada')
            ->whereDate('fecha_inicio', '<=', $fin->toDateString())
            ->whereDate('fecha_fin', '>=', $inicio->toDateString())
            ->get(['fecha_inicio', 'fecha_fin']);

        $fechas = [];

        foreach ($licencias as $licencia) {
            $desde = CarbonImmutable::parse($licencia->fecha_inicio);
            $hasta = CarbonImmutable::parse($licencia->fecha_fin);

            if ($desde->lessThan($inicio)) {
                $desde = $inicio;
            }

            if ($hasta->greaterThan($fin)) {
                $hasta = $fin;
            }

            for ($dia = $desde; $dia->lessThanOrEqualTo($hasta); $dia = $dia->addDay()) {
                $fechas[$dia->toDateString()] = true;
            }
        }

        return array_keys($fechas);
    }

    /**
     * Días con marca de entrada en el período, excluyendo días con licencia.
     */
    private function diasConAsistencia(Trabajador $trabajador, int $mes, int $anio): int
    {
        $inicio = CarbonImmutable::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->endOfMonth();

        $fechas = Asistencia::query()
            ->where('trabajador_id', $trabajador->id)
            ->whereBetween('fecha_hora_entrada', [$inicio, $fin])
            ->pluck('fecha_hora_entrada')
            ->map(fn ($fecha) => CarbonImmutable::parse($fecha)->toDateString())
            ->unique()
            ->values()
            ->all();

        if ($fechas === []) {
            throw new Exception(
                "No hay asistencias registradas para {$trabajador->nombres} {$trabajador->apellidos} en el período "
                .str_pad((string) $mes, 2, '0', STR_PAD_LEFT)."/{$anio}. Usa el modo licencias o ingresa los días manualmente.",
            );
        }

        $fechas = array_values(array_diff($fechas, $this->fechasConLicencia($trabajador, $mes, $anio)));

        if ($fechas === []) {
            throw new Exception(
                "Todas las asistencias de {$trabajador->nombres} {$trabajador->apellidos} coinciden con licencias médicas. Revisa el período.",
            );
        }

        return $this->validarDias(min(30, count($fechas)));
    }

    private function contratoVigente(Trabajador $trabajador, int $mes, int $anio): Contrato
    {
        $inicioPeriodo = CarbonImmutable::create($anio, $mes, 1)->startOfMonth();
        $finPeriodo = $inicioPeriodo->endOfMonth();

        $contrato = $trabajador->contratos()
            ->whereDate('fecha_inicio', '<=', $finPeriodo->toDateString())
            ->where(function ($query) use ($inicioPeriodo): void {
                $query->whereNull('fecha_termino')
                    ->orWhereDate('fecha_termino', '>=', $inicioPeriodo->toDateString());
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        if (! $contrato) {
            throw new Exception("El trabajador no tiene un contrato vigente para el período $mes-$anio.");
        }

        return $contrato;
    }

    /**
     * @param  array<string, mixed>  $opciones
     */
    private function montoOpcion(array $opciones, string $clave): int
    {
        $valor = $opciones[$clave] ?? 0;

        if (! is_numeric($valor) || (int) $valor < 0) {
            throw new Exception("El monto '{$clave}' debe ser un número positivo.");
        }

        return (int) $valor;
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function datosAfp(Trabajador $trabajador): array
    {
        $clave = $this->claveAfp($trabajador->afp);

        if ($clave === '') {
            throw new Exception('El trabajador no tiene AFP registrada. Complétala antes de generar la liquidación.');
        }

        $tasa = $trabajador->afp_tasa;

        if ($tasa === null) {
            $tasa = config("legal.afp_tasas.{$clave}");
        }

        if ($tasa === null) {
            throw new Exception(
                "No hay una tasa configurada para la AFP '{$trabajador->afp}'. Regístrala en la ficha del trabajador.",
            );
        }

        $tasa = (float) $tasa;

        if ($tasa < 10 || $tasa > 20) {
            throw new Exception(
                "La tasa AFP de {$trabajador->nombres} ({$tasa}%) no es razonable. Debe incluir el 10% obligatorio más la comisión.",
            );
        }

        $nombres = [
            'capital' => 'Capital',
            'cuprum' => 'Cuprum',
            'habitat' => 'Habitat',
            'modelo' => 'Modelo',
            'planvital' => 'PlanVital',
            'provida' => 'Provida',
            'uno' => 'Uno',
        ];

        return [$nombres[$clave] ?? ucfirst($clave), $tasa];
    }

    /**
     * @return array{0: string, 1: float, 2: int}
     */
    private function datosSalud(Trabajador $trabajador, ParametroLegal $parametro, int $baseImponible): array
    {
        $clave = $this->normalizar($trabajador->sistema_salud);

        if ($clave === '') {
            throw new Exception('El trabajador no tiene sistema de salud registrado. Complétalo antes de generar la liquidación.');
        }

        $tasaMinima = (float) config('legal.salud_cotizacion_minima', 7.0);

        if (str_contains($clave, 'isapre')) {
            $planUf = $trabajador->salud_plan_uf;

            if ($planUf === null || $planUf <= 0) {
                throw new Exception(
                    "El trabajador {$trabajador->nombres} está en Isapre, pero no tiene registrado el plan en UF. Complétalo antes de liquidar.",
                );
            }

            $montoMinimo = (int) round($baseImponible * ($tasaMinima / 100));
            $montoPlan = (int) round((float) $planUf * (float) $parametro->valor_uf);
            $monto = max($montoMinimo, $montoPlan);
            $porcentaje = $baseImponible > 0
                ? round($monto / $baseImponible * 100, 2)
                : $tasaMinima;

            return ['Isapre', $porcentaje, $monto];
        }

        if (str_contains($clave, 'fonasa')) {
            return ['Fonasa', $tasaMinima, (int) round($baseImponible * ($tasaMinima / 100))];
        }

        throw new Exception(
            "El sistema de salud '{$trabajador->sistema_salud}' no es reconocido. Usa Fonasa o Isapre.",
        );
    }

    private function claveAfp(?string $afp): string
    {
        return trim(preg_replace('/\bafp\b/', '', $this->normalizar($afp)) ?? '');
    }

    private function normalizar(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', Buscador::normalizar($texto)) ?? '');
    }
}
