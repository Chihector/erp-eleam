<?php

namespace App\Filament\Resources\Turnos\Tables;

use App\Support\Buscador;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TurnosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn ($record) => "{$record->trabajador?->nombres} {$record->trabajador?->apellidos}")
                    ->description(fn ($record) => 'Turno: '.$record->tipo_turno)
                    ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos')),

                TextColumn::make('fecha_inicio')
                    ->label('Fecha y Horario')
                    ->formatStateUsing(fn ($record) => Carbon::parse($record->fecha_inicio)->format('d/m/Y').' ('.Carbon::parse($record->fecha_inicio)->format('H:i').' a '.Carbon::parse($record->fecha_fin)->format('H:i').')')
                    ->sortable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Programado' => 'warning',
                        'Completado' => 'success',
                        'Ausente' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('fecha_fin')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
