<?php

namespace App\Filament\Resources\Vacacions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class VacacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Nombres')
                    ->searchable(),
                TextColumn::make('trabajador.apellidos')
                    ->label('Apellidos')
                    ->searchable(),
                TextColumn::make('fecha_inicio')
                    ->label('Desde')
                    ->date('d/m/Y'),
                TextColumn::make('fecha_fin')
                    ->label('Hasta')
                    ->date('d/m/Y'),
                TextColumn::make('dias_habiles')
                    ->label('Días'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Aprobada' => 'success',
                        'Rechazada' => 'danger',
                        default => 'gray',
                    }),
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
