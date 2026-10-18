<?php

namespace App\Services;

use App\Models\ParametroLegal;
use App\Models\Trabajador;
use Carbon\CarbonInterface;
use Exception;

/**
 * Cálculo de finiquitos según normativa laboral chilena (versión
 * simplificada para ELEAM):
 *
 *  - Indemnización por años de servicio (Art. 161 "Necesidades de la
 *    Empresa"): 30 días de la última remuneración por año de servicio,
 *    con tope de 11 años y sueldo tope de 90 UF.
 *  - Indemnización sustitutiva del aviso previo: 1 mes de sueldo.
 *  - Vacaciones pendientes: saldo legal no gozado valorizado por día.
 *  - Gratificación proporcional a los meses del año en que se termina.
 */
class FiniquitoService
{
    /** Tope legal de años indemnizables. */
    private const TOPE_ANIOS = 11;

    /** Tope en UF del sueldo usado para la indemnización. */
    private const TOPE_UF_INDEMNIZACION = 90.0;

    /**
     * Calcula los montos del finiquito.
     *
     * @return array{anios_servicio: int, meses_servicio: int, sueldo_base_calculo: int, indemnizacion_anios: int, indemnizacion_aviso_previo: int, vacaciones_pendientes: int, gratificacion_pendiente: int, total_haberes: int, liquido_pagar: int}
     *
     * @throws Exception Si el trabajador no tiene contrato o faltan parámetros legales.
     */
    public function calcular(Trabajador $trabajador, string $causal, CarbonInterface $fechaTermino): array
    {
        $contrato = $trabajador->contratos()->latest()->first();

        if (! $contrato) {
            throw new Exception('El trabajador no tiene un contrato registrado.');
        }

        $parametro = ParametroLegal::query()
            ->where('mes', $fechaTermino->month)
            ->where('anio', $fechaTermino->year)
            ->first()
            ?? ParametroLegal::query()->latest('anio')->latest('mes')->first();

        if (! $parametro) {
            throw new Exception('No existen parámetros legales configurados para calcular el finiquito.');
        }

        // ── Antigüedad ───────────────────────────────────────────────────────
        $ingreso = $trabajador->fecha_ingreso ?? $fechaTermino;
        $aniosCompletos = (int) $ingreso->diffInYears($fechaTermino);
        $mesesRestantes = (int) ($ingreso->diffInMonths($fechaTermino) % 12);

        // ── Sueldo de cálculo con tope de 90 UF ──────────────────────────────
        $sueldoBase = (int) $contrato->sueldo_base;
        $topeIndemnizacion = (int) round(self::TOPE_UF_INDEMNIZACION * (float) $parametro->valor_uf);
        $sueldoCalculo = min($sueldoBase, $topeIndemnizacion);

        // ── Indemnizaciones (solo Necesidades de la Empresa, Art. 161) ───────
        $causalNormalizada = mb_strtolower($causal);
        $esNecesidadesEmpresa = str_contains($causalNormalizada, 'necesidades')
            || str_contains($causalNormalizada, '161');

        if ($esNecesidadesEmpresa) {
            $aniosIndemnizables = min($aniosCompletos, self::TOPE_ANIOS);
            $indemnizacionAnios = (int) round($aniosIndemnizables * $sueldoCalculo);
            $indemnizacionAviso = $sueldoCalculo;
        } else {
            $indemnizacionAnios = 0;
            $indemnizacionAviso = 0;
        }

        // ── Vacaciones pendientes (saldo legal no gozado) ────────────────────
        $saldoVacaciones = max(0.0, (float) $trabajador->saldo_vacaciones);
        $sueldoDiario = $sueldoBase / 30;
        $vacacionesPendientes = (int) round($saldoVacaciones * $sueldoDiario);

        // ── Gratificación proporcional (meses del año hasta el término) ──────
        $mesesDelAnio = (int) $fechaTermino->month;
        $topeMensualGratificacion = (4.75 * $parametro->sueldo_minimo) / 12;
        $gratificacionMensual = min($sueldoBase * 0.25, $topeMensualGratificacion);
        $gratificacionProporcional = (int) round($gratificacionMensual * $mesesDelAnio);

        $totalHaberes = $indemnizacionAnios
            + $indemnizacionAviso
            + $vacacionesPendientes
            + $gratificacionProporcional;

        return [
            'anios_servicio' => $aniosCompletos,
            'meses_servicio' => $mesesRestantes,
            'sueldo_base_calculo' => $sueldoCalculo,
            'indemnizacion_anios' => $indemnizacionAnios,
            'indemnizacion_aviso_previo' => $indemnizacionAviso,
            'vacaciones_pendientes' => $vacacionesPendientes,
            'gratificacion_pendiente' => $gratificacionProporcional,
            'total_haberes' => $totalHaberes,
            'liquido_pagar' => $totalHaberes,
        ];
    }
}
