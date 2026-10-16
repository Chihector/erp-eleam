<?php

namespace App\Filament\Resources\Trabajadors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;

class TrabajadorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rut')->label('RUT')->searchable(),
                TextColumn::make('nombres')->label('Nombres')->searchable(),
                TextColumn::make('apellidos')->label('Apellidos')->searchable(),
                TextColumn::make('cargo')->label('Cargo')->searchable(),
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
