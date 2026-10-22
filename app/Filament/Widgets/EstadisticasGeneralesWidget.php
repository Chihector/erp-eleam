<?php

namespace App\Filament\Widgets;

use App\Models\Contrato;
use App\Models\Finiquito;
use App\Models\LicenciaMedica;
use App\Models\Trabajador;
use App\Models\Vacacion;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class EstadisticasGeneralesWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Se cachean solo los números (5 min), no los objetos de UI.
        $datos = Cache::remember('dashboard:estadisticas', 300, function (): array {
            $hoy = Carbon::today();

            return [
                'trabajadores' => Trabajador::count(),
                'contratos_por_vencer' => Contrato::whereNotNull('fecha_termino')
                    ->whereBetween('fecha_termino', [$hoy->toDateString(), $hoy->copy()->addDays(30)->toDateString()])
                    ->count(),
                'licencias_activas' => LicenciaMedica::whereIn('estado', ['Pendiente', 'Validada'])
                    ->where('fecha_fin', '>=', $hoy->toDateString())
                    ->count(),
                'finiquitos_pendientes' => Finiquito::where('estado', 'Borrador')->count(),
                'vacaciones_pendientes' => Vacacion::where('estado', 'Pendiente')->count(),
                'vacaciones_vencidas' => Trabajador::conVacacionesPendientesMayorA(15)->count(),
            ];
        });

        return [
            Stat::make('Total Trabajadores', $datos['trabajadores'])
                ->description('Activos en el sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Contratos por vencer (30 días)', $datos['contratos_por_vencer'])
                ->description('Requieren renovación o finiquito')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($datos['contratos_por_vencer'] > 0 ? 'warning' : 'success'),

            Stat::make('Licencias médicas activas', $datos['licencias_activas'])
                ->description('En curso actualmente')
                ->descriptionIcon('heroicon-m-heart')
                ->color($datos['licencias_activas'] > 0 ? 'info' : 'success'),

            Stat::make('Finiquitos pendientes', $datos['finiquitos_pendientes'])
                ->description('Por procesar o firmar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($datos['finiquitos_pendientes'] > 0 ? 'warning' : 'success'),

            Stat::make('Solicitudes de vacaciones pendientes', $datos['vacaciones_pendientes'])
                ->description('Por aprobar o rechazar')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($datos['vacaciones_pendientes'] > 0 ? 'warning' : 'success'),

            Stat::make('Trabajadores con vacaciones > 15 días', $datos['vacaciones_vencidas'])
                ->description('Riesgo de multa DT (saldo acumulado)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($datos['vacaciones_vencidas'] > 0 ? 'danger' : 'success'),
        ];
    }
}
