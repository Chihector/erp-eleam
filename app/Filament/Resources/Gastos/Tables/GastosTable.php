<?php

namespace App\Filament\Resources\Gastos\Tables;

use App\Models\Gasto;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class GastosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->searchable(),
                TextColumn::make('monto')
                    ->label('Monto')
                    ->money('clp')
                    ->sortable(),
                TextColumn::make('comprobante')
                    ->label('Comprobante')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Adjunto' : 'Sin adjunto')
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
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
                Action::make('descargar_comprobante')
                    ->label('Ver comprobante')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (Gasto $record): bool => filled($record->comprobante))
                    ->action(function (Gasto $record) {
                        foreach (['local', 'public'] as $disco) {
                            if (Storage::disk($disco)->exists($record->comprobante)) {
                                return Storage::disk($disco)->download($record->comprobante);
                            }
                        }

                        Notification::make()
                            ->title('No se encontró el archivo adjunto')
                            ->body('Puede haber sido movido o eliminado del almacenamiento.')
                            ->warning()
                            ->send();

                        return null;
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
