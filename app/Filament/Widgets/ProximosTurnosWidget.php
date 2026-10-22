<?php

namespace App\Filament\Widgets;

use App\Models\Turno;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProximosTurnosWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Próximos Turnos (14 días)';

    protected static ?int $headingLevel = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->consultaTurnos())
            ->columns([
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha y horario')
                    ->formatStateUsing(fn (Turno $record): string => Carbon::parse($record->fecha_inicio)->format('d/m/Y H:i')
                        .' a '.Carbon::parse($record->fecha_fin)->format('H:i'))
                    ->sortable()
                    ->badge()
                    ->color(fn (Turno $record): string => Carbon::parse($record->fecha_inicio)->isToday()
                        ? 'success'
                        : (Carbon::parse($record->fecha_inicio)->isTomorrow() ? 'warning' : 'primary')),

                Tables\Columns\TextColumn::make('tipo_turno')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Dia' => 'success',
                        'Noche' => 'danger',
                        'Cuarto Turno' => 'info',
                        'Libre' => 'gray',
                        'Vacaciones' => 'primary',
                        'Licencia Medica' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn (Turno $record): string => trim(($record->trabajador?->nombres ?? '').' '.($record->trabajador?->apellidos ?? '')))
                    ->description(fn (Turno $record): ?string => $record->trabajador?->cargo)
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Programado' => 'warning',
                        'Completado' => 'success',
                        'Ausente' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('fecha_inicio', 'asc')
            ->paginated([10, 25])
            ->poll('60s')
            ->emptyStateHeading('No hay turnos programados')
            ->emptyStateDescription('Los turnos de los próximos 14 días aparecerán aquí.');
    }

    private function consultaTurnos(): Builder
    {
        return Turno::query()
            ->with('trabajador')
            ->where('fecha_inicio', '>=', Carbon::today())
            ->where('fecha_inicio', '<=', Carbon::today()->addDays(14)->endOfDay())
            ->where('estado', '!=', 'Ausente')
            ->orderBy('fecha_inicio');
    }
}
