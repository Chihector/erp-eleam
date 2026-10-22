<?php

namespace App\Filament\Resources\Liquidacions\Tables;

use App\Models\Liquidacion;
use App\Services\RemuneracionService;
use App\Support\Buscador;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
                    ->description(fn (Liquidacion $record) => 'RUT: '.($record->trabajador?->rut ?? '-'))
                    ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos'))
                    ->sortable(),

                TextColumn::make('trabajador.rut')
                    ->label('RUT')
                    ->searchable(query: Buscador::relacion('trabajador', 'rut'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('periodo')
                    ->label('Período')
                    ->state(fn (Liquidacion $record) => str_pad($record->mes, 2, '0', STR_PAD_LEFT).'/'.$record->anio)
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('sueldo_liquido')
                    ->label('Líquido')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('impuesto_segunda_categoria')
                    ->label('IUSC')
                    ->formatStateUsing(fn ($state) => '$'.number_format((int) $state, 0, ',', '.'))
                    ->color('danger')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('dias_trabajados')
                    ->label('Días Trab.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('origen_dias')
                    ->label('Origen Días')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'licencias' => 'Licencias',
                        'asistencia' => 'Asistencia',
                        default => 'Manual',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'licencias' => 'info',
                        'asistencia' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sueldo_base')
                    ->label('Sueldo Base')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('renta_liquida_imponible')
                    ->label('Renta Líquida Imponible')
                    ->formatStateUsing(fn ($state) => '$'.number_format((int) $state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('descuento_anticipo')
                    ->label('Anticipo')
                    ->formatStateUsing(fn ($state) => '$'.number_format((int) $state, 0, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('descuento_sindicato')
                    ->label('Cuota Sindical')
                    ->formatStateUsing(fn ($state) => '$'.number_format((int) $state, 0, ',', '.'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_haberes')
                    ->label('Total Haberes')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_descuentos')
                    ->label('Total Descuentos')
                    ->formatStateUsing(fn ($state) => '$'.number_format($state, 0, ',', '.'))
                    ->color('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('cerrada')
                    ->label('Cerrada')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('cerrada_en')
                    ->label('Cerrada el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cerradaPor.nombre')
                    ->label('Cerrada por')
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

                EditAction::make()
                    ->visible(fn (Liquidacion $record): bool => ! $record->cerrada
                        && (auth()->user()?->can('update', $record) ?? false)),

                Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Cerrar liquidación')
                    ->modalDescription('Al cerrar, la liquidación queda inmutable. Solo un administrador puede reabrirla con un motivo registrado. Se exige que los parámetros legales del período estén verificados.')
                    ->modalSubmitActionLabel('Cerrar liquidación')
                    ->visible(fn (Liquidacion $record): bool => ! $record->cerrada
                        && (auth()->user()?->can('cerrar', $record) ?? false))
                    ->action(function (Liquidacion $record): void {
                        $usuario = auth()->user();

                        if (! $usuario) {
                            return;
                        }

                        try {
                            app(RemuneracionService::class)->cerrar($record, $usuario);

                            Notification::make()
                                ->title('Liquidación cerrada correctamente.')
                                ->body('Quedó bloqueada para edición y recálculo.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('No se pudo cerrar la liquidación')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('reabrir')
                    ->label('Reabrir')
                    ->icon('heroicon-o-lock-open')
                    ->color('danger')
                    ->modalHeading('Reabrir liquidación cerrada')
                    ->modalDescription('La reapertura queda registrada con tu usuario, la fecha y el motivo. Usa esta acción solo para corregir un error documentado.')
                    ->modalSubmitActionLabel('Reabrir liquidación')
                    ->form([
                        Textarea::make('motivo')
                            ->label('Motivo de la reapertura')
                            ->required()
                            ->minLength(10)
                            ->maxLength(1000)
                            ->helperText('Explica qué error se corrigió. Este texto queda en la auditoría.'),
                    ])
                    ->visible(fn (Liquidacion $record): bool => $record->cerrada
                        && (auth()->user()?->can('reabrir', $record) ?? false))
                    ->action(function (Liquidacion $record, array $data): void {
                        $usuario = auth()->user();

                        if (! $usuario) {
                            return;
                        }

                        try {
                            app(RemuneracionService::class)->reabrir(
                                $record,
                                $usuario,
                                (string) ($data['motivo'] ?? ''),
                            );

                            Notification::make()
                                ->title('Liquidación reabierta.')
                                ->body('Ya se puede corregir y recalcular. El motivo quedó registrado.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('No se pudo reabrir la liquidación')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }
}
