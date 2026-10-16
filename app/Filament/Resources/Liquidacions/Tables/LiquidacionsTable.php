<?php

namespace App\Filament\Resources\Liquidacions\Tables;

use App\Models\Liquidacion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LiquidacionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn (Liquidacion $record) => "{$record->trabajador?->nombres} {$record->trabajador?->apellidos}")
                    ->description(fn (Liquidacion $record) => 'RUT: ' . ($record->trabajador?->rut ?? '-'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trabajador.rut')
                    ->label('RUT')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('periodo')
                    ->label('Período')
                    ->state(fn (Liquidacion $record) => str_pad($record->mes, 2, '0', STR_PAD_LEFT) . '/' . $record->anio)
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('sueldo_liquido')
                    ->label('Líquido')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('dias_trabajados')
                    ->label('Días Trab.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sueldo_base')
                    ->label('Sueldo Base')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_haberes')
                    ->label('Total Haberes')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_descuentos')
                    ->label('Total Descuentos')
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 0, ',', '.'))
                    ->color('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('cerrada')
                    ->label('Cerrada')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Fecha Generación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Liquidacion $record) => route('liquidaciones.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

