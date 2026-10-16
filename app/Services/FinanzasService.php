<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;

class FinanzasService
{
    /**
     * Total de todos los gastos registrados.
     */
    public function totalGastos(): float
    {
        return (float) Gasto::sum('monto');
    }

    /**
     * Total de todos los ingresos registrados.
     */
    public function totalIngresos(): float
    {
        return (float) Ingreso::sum('monto');
    }

    /**
     * Saldo disponible: total de ingresos menos total de gastos.
     */
    public function saldoDisponible(): float
    {
        return $this->totalIngresos() - $this->totalGastos();
    }

    /**
     * Totales de ingresos y gastos del mes/año indicados.
     *
     * @return array{ingresos: float, gastos: float}
     */
    public function totalDelMes(int $anio, int $mes): array
    {
        return [
            'ingresos' => (float) Ingreso::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->sum('monto'),
            'gastos' => (float) Gasto::whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->sum('monto'),
        ];
    }
}
