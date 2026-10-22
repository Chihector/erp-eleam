<?php

namespace App\Filament\Widgets;

use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class FinanzasChartWidget extends ChartWidget
{
    protected ?string $heading = 'Gastos vs Ingresos (últimos 12 meses)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $desde = Carbon::now()->subMonths(11)->startOfMonth();

        // Dos consultas en total (portables SQLite/PostgreSQL) y agrupación en PHP.
        $gastosPorMes = Gasto::query()
            ->where('fecha', '>=', $desde->toDateString())
            ->get(['fecha', 'monto'])
            ->groupBy(fn (Gasto $gasto): string => Carbon::parse($gasto->fecha)->format('Y-m'))
            ->map(fn ($grupo): float => (float) $grupo->sum('monto'));

        $ingresosPorMes = Ingreso::query()
            ->where('fecha', '>=', $desde->toDateString())
            ->get(['fecha', 'monto'])
            ->groupBy(fn (Ingreso $ingreso): string => Carbon::parse($ingreso->fecha)->format('Y-m'))
            ->map(fn ($grupo): float => (float) $grupo->sum('monto'));

        $meses = [];
        $datosGastos = [];
        $datosIngresos = [];

        for ($i = 11; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $clave = $fecha->format('Y-m');

            $meses[] = $fecha->locale('es')->isoFormat('MMM YYYY');
            $datosGastos[] = $gastosPorMes[$clave] ?? 0.0;
            $datosIngresos[] = $ingresosPorMes[$clave] ?? 0.0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Gastos',
                    'data' => $datosGastos,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Ingresos',
                    'data' => $datosIngresos,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $meses,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
