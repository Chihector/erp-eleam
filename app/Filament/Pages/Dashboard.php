<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActividadRecienteWidget;
use App\Filament\Widgets\EstadisticasGeneralesWidget;
use App\Filament\Widgets\FinanzasChartWidget;
use App\Filament\Widgets\ProximosTurnosWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            EstadisticasGeneralesWidget::class,
            FinanzasChartWidget::class,
            ActividadRecienteWidget::class,
            ProximosTurnosWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
