<?php

namespace App\Filament\Resources\Users\Tables;

use App\Support\Buscador;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombres')->searchable(query: Buscador::columna('nombre')),
                TextColumn::make('apellido')->label('Apellidos')->searchable(query: Buscador::columna('apellido')),
                TextColumn::make('rut')->label('RUT')->searchable(query: Buscador::columna('rut')),
                TextColumn::make('email')->label('Correo')->searchable(),
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
