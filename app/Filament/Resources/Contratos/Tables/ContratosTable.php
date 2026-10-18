<?php

namespace App\Filament\Resources\Contratos\Tables;

use App\Support\Buscador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContratosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.rut')->label('RUT Trabajador')->searchable(query: Buscador::relacion('trabajador', 'rut')),
                TextColumn::make('tipo_contrato')->label('Tipo')->searchable(),
                TextColumn::make('sueldo_base')->label('Sueldo Base')->money('CLP'),
                TextColumn::make('horas_semanales')->label('Horas Semanales'),
                TextColumn::make('fecha_inicio')->label('Fecha Inicio')->date(),
            ])
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
