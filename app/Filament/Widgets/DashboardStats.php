<?php

namespace App\Filament\Widgets;

use App\Services\FinanzasService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    /**
     * Clave de caché y duración (en segundos) de las estadísticas del dashboard.
     */
    private const CACHE_KEY = 'dashboard-stats';

    protected function getStats(): array
    {
        // Las estadísticas cambian con cada movimiento, por eso solo se
        // cachea durante un breve periodo; la interfaz sigue mostrando
        // la información de forma prácticamente "en tiempo real".
        $totales = Cache::remember(
            self::CACHE_KEY,
            60,
            fn () => $this->calcularTotales(),
        );

        $hoy = Carbon::now();

        return [
            Stat::make('Dinero Disponible (Caja)', '$' . number_format($totales['saldo'], 0, ',', '.'))
                ->description('Balance general (Ingresos totales - Gastos totales)')
                ->color($totales['saldo'] >= 0 ? 'success' : 'danger'),

            Stat::make('Ingresos de ' . ucfirst($hoy->translatedFormat('F')), '$' . number_format($totales['ingresosMes'], 0, ',', '.'))
                ->description('Total recaudado este mes')
                ->color('success'),

            Stat::make('Gastos de ' . ucfirst($hoy->translatedFormat('F')), '$' . number_format($totales['gastosMes'], 0, ',', '.'))
                ->description('Resumen mensual de gastos')
                ->color('warning'),
        ];
    }

    /**
     * Calcula los totales con los que se mostrarán las estadísticas.
     *
     * @return array{saldo: float, ingresosMes: float, gastosMes: float}
     */
    private function calcularTotales(): array
    {
        $anio = Carbon::now()->year;
        $mes = Carbon::now()->month;

        return [
            'ingresosMes' => app(FinanzasService::class)->totalDelMes($anio, $mes)['ingresos'],
            'gastosMes' => app(FinanzasService::class)->totalDelMes($anio, $mes)['gastos'],
            'saldo' => app(FinanzasService::class)->saldoDisponible(),
        ];
    }
}
