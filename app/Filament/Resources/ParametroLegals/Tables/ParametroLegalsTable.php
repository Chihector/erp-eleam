<?php

namespace App\Filament\Resources\ParametroLegals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParametroLegalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('anio')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_uf')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_utm')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sueldo_minimo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tope_imponible_afp')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tope_imponible_ips')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tope_imponible_cesantia')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('porcentaje_sis')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
