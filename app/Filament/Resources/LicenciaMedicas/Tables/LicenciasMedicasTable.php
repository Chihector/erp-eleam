<?php

namespace App\Filament\Resources\LicenciaMedicas\Tables;

use App\Support\Buscador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenciasMedicasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn ($record) => "{$record->trabajador->nombres} {$record->trabajador->apellidos}")
                    ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos')),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Maternal' => 'info',
                        'Accidente del Trabajo', 'Enfermedad Profesional' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('fecha_inicio')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('fecha_fin')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('dias_reposo')
                    ->label('Días')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('dias_pagados')
                    ->label('Días Pagados')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('monto_subsidio')
                    ->label('Subsidio')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable(),

                TextColumn::make('entidad_emisora')
                    ->label('Entidad')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Validada' => 'success',
                        'Rechazada' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('fecha_inicio', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Validada' => 'Validada',
                        'Rechazada' => 'Rechazada',
                    ]),
                SelectFilter::make('tipo')
                    ->label('Tipo de Licencia')
                    ->options([
                        'Común' => 'Común',
                        'Maternal' => 'Maternal',
                        'Accidente del Trabajo' => 'Accidente del Trabajo',
                        'Enfermedad Profesional' => 'Enfermedad Profesional',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
