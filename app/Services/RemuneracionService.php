<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Support\Buscador;
use Carbon\CarbonImmutable;
use Exception;

class RemuneracionService
{
    /**
     * Calcula y genera la liquidación de sueldo para un trabajador en un mes y año específico.
     *
     * Los datos previsionales se toman del trabajador:
     *  - afp_tasa: 10% obligatorio + comisión vigente de su AFP.
     *  - salud_plan_uf: plan de Isapre en UF (el mínimo legal es 7% del imponible).
     *
     * El SIS se calcula como aporte del empleador y NO se descuenta del líquido.
     */
    public function calcularLiquidacion(Trabajador $trabajador, int $mes, int $anio, int $diasTrabajados = 30): Liquidacion
    {
        if ($diasTrabajados < 1 || $diasTrabajados > 30) {
            throw new Exception('Los días trabajados deben estar entre 1 y 30.');
        }

        // 1. Obtener parámetros legales del mes
        $parametroLegal = ParametroLegal::where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (! $parametroLegal) {
            throw new Exception("No existen parámetros legales configurados para el período $mes-$anio.");
        }

        // 2. Obtener el contrato vigente durante el período
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

        $sueldoBaseContrato = (int) $contrato->sueldo_base;

        // Proporción de días trabajados
        $proporcionDias = $diasTrabajados / 30;

        // 3. Haberes Imponibles
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

        // 4. Descuentos Legales
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

        $totalDescuentosLegales = $montoAfp + $montoSalud + $montoCesantia;

        // SIS: aporte del empleador, informativo. No se descuenta del trabajador.
        $sisPorcentaje = (float) $parametroLegal->porcentaje_sis;
        $montoSis = (int) round($baseCalculoAfp * ($sisPorcentaje / 100));

        // 5. Haberes No Imponibles (Asignaciones)
        // Podrían venir del contrato
        $montoColacion = 0; // (int) round(($contrato->monto_colacion ?? 0) * $proporcionDias);
        $montoMovilizacion = 0; // (int) round(($contrato->monto_movilizacion ?? 0) * $proporcionDias);
        $cargasFamiliares = 0;

        $totalNoImponible = $montoColacion + $montoMovilizacion + $cargasFamiliares;

        // Total Haberes
        $totalHaberes = $totalImponible + $totalNoImponible;

        // Otros Descuentos
        $otrosDescuentos = 0;
        $totalDescuentos = $totalDescuentosLegales + $otrosDescuentos;

        // 6. Líquido a Pagar
        $sueldoLiquido = $totalHaberes - $totalDescuentos;

        // 7. Crear o actualizar la liquidación
        $liquidacion = Liquidacion::updateOrCreate(
            [
                'trabajador_id' => $trabajador->id,
                'mes' => $mes,
                'anio' => $anio,
            ],
            [
                'contrato_id' => $contrato->id,
                'dias_trabajados' => $diasTrabajados,
                'sueldo_base' => $sueldoBaseProporcional,
                'gratificacion' => $gratificacion,
                'total_imponible' => $totalImponible,
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
                'sis_porcentaje' => $sisPorcentaje,
                'monto_sis' => $montoSis,
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
     * Tasa AFP del trabajador o, si no está registrada, la de referencia.
     *
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
