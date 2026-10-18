<?php

namespace App\Services;

use App\Models\LicenciaMedica;
use App\Models\ParametroLegal;

/**
 * Cálculo del subsidio de licencias médicas según normativa chilena
 * (versión simplificada para ELEAM):
 *
 *  - Licencia Común:
 *      · Subsidio = 75% del sueldo diario.
 *      · Tope del sueldo de referencia: 60 UF mensuales.
 *      · Carencia: los primeros 3 días no se pagan.
 *
 *  - Licencia Maternal y Accidente/Enfermedad Profesional (Mutual):
 *      · Subsidio = 100% del sueldo diario.
 *      · Sin carencia.
 *
 * Todos los montos quedan expresados en pesos chilenos (enteros).
 */
class SubsidioLicenciaService
{
    /**
     * Calcula el subsidio de una licencia médica.
     *
     * @return array{dias_pagados: int, porcentaje: float, subsidio_diario: int, subsidio_total: int}
     */
    public function calcular(LicenciaMedica $licencia): array
    {
        $sinDatos = [
            'dias_pagados' => 0,
            'porcentaje' => 0.0,
            'subsidio_diario' => 0,
            'subsidio_total' => 0,
        ];

        $trabajador = $licencia->trabajador;

        if (! $trabajador || ! $licencia->fecha_inicio) {
            return $sinDatos;
        }

        $contrato = $trabajador->contratos()->latest()->first();

        if (! $contrato) {
            return $sinDatos;
        }

        // Parámetros legales del período de la licencia (o el más reciente).
        $parametro = ParametroLegal::query()
            ->where('mes', $licencia->fecha_inicio->month)
            ->where('anio', $licencia->fecha_inicio->year)
            ->first()
            ?? ParametroLegal::query()->latest('anio')->latest('mes')->first();

        if (! $parametro) {
            return $sinDatos;
        }

        $diasReposo = max(0, (int) ($licencia->dias_reposo ?: 0));

        if ($diasReposo === 0) {
            return $sinDatos;
        }

        $sueldoDiario = $contrato->sueldo_base / 30;

        $esLaboral = str_contains(mb_strtolower($licencia->tipo), 'accidente')
            || str_contains(mb_strtolower($licencia->tipo), 'profesional');
        $esMaternal = str_contains(mb_strtolower($licencia->tipo), 'maternal');

        if ($esLaboral || $esMaternal) {
            // 100% del sueldo, desde el primer día y sin tope.
            $porcentaje = 1.0;
            $diasPagados = $diasReposo;
            $baseDiaria = $sueldoDiario;
        } else {
            // Común: 75% del sueldo diario, tope de 60 UF mensuales y
            // 3 días de carencia (los paga el empleado o no se pagan).
            $porcentaje = 0.75;
            $topeDiario = (60 * (float) $parametro->valor_uf) / 30;
            $baseDiaria = min($sueldoDiario, $topeDiario);
            $diasPagados = max(0, $diasReposo - 3);
        }

        $subsidioDiario = (int) round($baseDiaria * $porcentaje);

        return [
            'dias_pagados' => $diasPagados,
            'porcentaje' => $porcentaje,
            'subsidio_diario' => $subsidioDiario,
            'subsidio_total' => (int) round($subsidioDiario * $diasPagados),
        ];
    }
}
