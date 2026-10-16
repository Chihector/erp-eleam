<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $gastosEsteMes = Gasto::whereMonth('fecha', Carbon::now()->month)
            ->whereYear('fecha', Carbon::now()->year)
            ->sum('monto');
            
        $gastosTotales = Gasto::sum('monto');

        $ingresosEsteMes = Ingreso::whereMonth('fecha', Carbon::now()->month)
            ->whereYear('fecha', Carbon::now()->year)
            ->sum('monto');

        $ingresosTotales = Ingreso::sum('monto');

        // Saldo real basado en todos los ingresos y gastos registrados
        $saldoActual = $ingresosTotales - $gastosTotales;

        return [
            Stat::make('Dinero Disponible (Caja)', '$' . number_format($saldoActual, 0, ',', '.'))
                ->description('Balance general (Ingresos totales - Gastos totales)')
                ->color($saldoActual >= 0 ? 'success' : 'danger'),
                
            Stat::make('Ingresos de ' . ucfirst(Carbon::now()->translatedFormat('F')), '$' . number_format($ingresosEsteMes, 0, ',', '.'))
                ->description('Total recaudado este mes')
                ->color('success'),

            Stat::make('Gastos de ' . ucfirst(Carbon::now()->translatedFormat('F')), '$' . number_format($gastosEsteMes, 0, ',', '.'))
                ->description('Resumen mensual de gastos')
                ->color('warning'),
        ];
    }
}
