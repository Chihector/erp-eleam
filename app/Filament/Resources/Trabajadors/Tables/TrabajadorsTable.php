<?php

namespace App\Filament\Resources\Trabajadors\Tables;

use App\Support\Buscador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TrabajadorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rut')->label('RUT')->searchable(query: Buscador::columna('rut')),
                TextColumn::make('nombres')->label('Nombres')->searchable(query: Buscador::columna('nombres')),
                TextColumn::make('apellidos')->label('Apellidos')->searchable(query: Buscador::columna('apellidos')),
                TextColumn::make('cargo')->label('Cargo')->searchable(query: Buscador::columna('cargo')),
                TextColumn::make('afp')->label('AFP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('afp_tasa')->label('Tasa AFP')->suffix('%')->placeholder('Referencia')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sistema_salud')->label('Salud')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fecha_ingreso')->label('Fecha Ingreso')->date(),
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
