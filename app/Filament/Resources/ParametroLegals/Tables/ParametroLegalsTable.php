<?php

namespace App\Filament\Resources\ParametroLegals\Tables;

use App\Models\ParametroLegal;
use App\Services\ParametroLegalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParametroLegalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mes')
                    ->label('Mes')
                    ->formatStateUsing(fn (int $state): string => [
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                    ][$state] ?? (string) $state)
                    ->sortable(),
                TextColumn::make('anio')
                    ->label('Año')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_uf')
                    ->label('UF')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('valor_utm')
                    ->label('UTM')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('sueldo_minimo')
                    ->label('Sueldo mínimo')
                    ->formatStateUsing(fn ($state): string => '$'.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('tope_imponible_afp')
                    ->label('Tope AFP (UF)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tope_imponible_ips')
                    ->label('Tope IPS (UF)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tope_imponible_cesantia')
                    ->label('Tope Cesantía (UF)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('porcentaje_sis')
                    ->label('SIS (%)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ?: 'manual')
                    ->color(fn (?string $state): string => match (true) {
                        str_contains((string) $state, 'mindicador') => 'info',
                        str_contains((string) $state, 'semilla') => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('actualizado_en')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verificado_en')
                    ->label('Revisión')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Verificado' : 'Sin verificar')
                    ->color(fn (?string $state): string => $state ? 'success' : 'warning'),
                TextColumn::make('verificadoPor.email')
                    ->label('Verificado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar revisión de parámetros')
                    ->modalDescription('Confirma que revisaste la UF, UTM, sueldo mínimo, topes y SIS contra la normativa vigente. La acción queda registrada con tu usuario.')
                    ->visible(fn (ParametroLegal $record): bool => $record->verificado_en === null)
                    ->action(function (ParametroLegal $record): void {
                        app(ParametroLegalService::class)->marcarVerificado($record, (int) auth()->id());

                        Notification::make()
                            ->title('Período marcado como verificado')
                            ->body('Quedó registrado el usuario y la fecha de revisión.')
                            ->success()
                            ->send();
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
