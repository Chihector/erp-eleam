<?php

namespace App\Filament\Resources\Asistencias\Tables;

use App\Support\Buscador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AsistenciasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn ($record) => "{$record->trabajador->nombres} {$record->trabajador->apellidos}")
                    ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos')),
                TextColumn::make('fecha_hora_entrada')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('fecha_hora_salida')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Aún no sale')
                    ->sortable(),
                TextColumn::make('observaciones')
                    ->limit(30)
                    ->searchable(),
            ])
            ->defaultSort('fecha_hora_entrada', 'desc')
            ->filters([
                //
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
