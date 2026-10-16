<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use Exception;

class RemuneracionService
{
    /**
     * Calcula y genera la liquidación de sueldo para un trabajador en un mes y año específico.
     */
    public function calcularLiquidacion(Trabajador $trabajador, int $mes, int $anio, int $diasTrabajados = 30): Liquidacion
    {
        // 1. Obtener parámetros legales del mes
        $parametroLegal = ParametroLegal::where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (!$parametroLegal) {
            throw new Exception("No existen parámetros legales configurados para el período $mes-$anio.");
        }

        // 2. Obtener el contrato vigente del trabajador
        // Por simplicidad, tomamos el último contrato
        $contrato = $trabajador->contratos()->latest()->first();
        if (!$contrato) {
            throw new Exception("El trabajador no tiene un contrato registrado.");
        }

        $sueldoBaseContrato = $contrato->sueldo_base;

        // Proporción de días trabajados
        $proporcionDias = $diasTrabajados / 30;

        // 3. Haberes Imponibles
        $sueldoBaseProporcional = (int) round($sueldoBaseContrato * $proporcionDias);
        
        // Gratificación (25% del sueldo base con tope de 4.75 sueldos mínimos anuales / 12)
        // Tope mensual = (4.75 * sueldo minimo) / 12
        $topeGratificacion = (4.75 * $parametroLegal->sueldo_minimo) / 12;
        $gratificacionCalculada = $sueldoBaseProporcional * 0.25;
        $gratificacion = (int) round(min($gratificacionCalculada, $topeGratificacion));
        
        // Ajustamos la gratificación a los días trabajados si corresponde al tope
        if ($gratificacionCalculada > $topeGratificacion) {
             $gratificacion = (int) round($topeGratificacion * $proporcionDias);
        }

        $totalImponible = $sueldoBaseProporcional + $gratificacion;

        // Validar tope imponible AFP
        $topeImponibleAfpPesos = (int) round($parametroLegal->tope_imponible_afp * $parametroLegal->valor_uf);
        $baseCalculoAfp = min($totalImponible, $topeImponibleAfpPesos);

        // 4. Descuentos Legales
        // Datos por defecto (en un sistema real esto vendría del contrato o trabajador)
        // Por simplicidad usaremos AFP Modelo (10.58%) y Fonasa (7%)
        $afpNombre = 'Modelo';
        $afpPorcentaje = 10.58; 
        $montoAfp = (int) round($baseCalculoAfp * ($afpPorcentaje / 100));

        $saludNombre = 'Fonasa';
        $saludPorcentaje = 7.0;
        $montoSalud = (int) round($baseCalculoAfp * ($saludPorcentaje / 100));

        // Seguro de Cesantía (0.6% a cargo del trabajador si tiene contrato indefinido)
        $montoCesantia = 0;
        if (str_contains(strtolower($contrato->tipo_contrato ?? ''), 'indefinido')) {
            $topeImponibleCesantiaPesos = (int) round($parametroLegal->tope_imponible_cesantia * $parametroLegal->valor_uf);
            $baseCalculoCesantia = min($totalImponible, $topeImponibleCesantiaPesos);
            $montoCesantia = (int) round($baseCalculoCesantia * 0.006);
        }

        $totalDescuentosLegales = $montoAfp + $montoSalud + $montoCesantia;

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
                'total_descuentos_legales' => $totalDescuentosLegales,
                'otros_descuentos' => $otrosDescuentos,
                'total_descuentos' => $totalDescuentos,
                'sueldo_liquido' => $sueldoLiquido,
                'cerrada' => false,
            ]
        );

        return $liquidacion;
    }
}
