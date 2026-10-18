<?php

namespace App\Filament\Resources\Finiquitos\Tables;

use App\Models\Finiquito;
use App\Support\Buscador;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FiniquitosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trabajador.nombres')
                    ->label('Trabajador')
                    ->formatStateUsing(fn (Finiquito $record) => "{$record->trabajador->nombres} {$record->trabajador->apellidos}")
                    ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos')),

                TextColumn::make('fecha_termino')
                    ->label('Término')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('causal')
                    ->label('Causal')
                    ->badge()
                    ->color(fn (string $state): string => str_contains($state, '161') ? 'warning' : 'gray')
                    ->toggleable(),

                TextColumn::make('anios_servicio')
                    ->label('Años')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('indemnizacion_anios')
                    ->label('Indemnización Años')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable(),

                TextColumn::make('indemnizacion_aviso_previo')
                    ->label('Aviso Previo')
                    ->money('CLP', locale: 'es_CL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vacaciones_pendientes')
                    ->label('Vacaciones')
                    ->money('CLP', locale: 'es_CL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gratificacion_pendiente')
                    ->label('Gratificación')
                    ->money('CLP', locale: 'es_CL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('liquido_pagar')
                    ->label('Líquido a Pagar')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Firmado' => 'success',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('fecha_termino', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'Borrador' => 'Borrador',
                        'Firmado' => 'Firmado',
                    ]),
            ])
            ->recordActions([
                Action::make('pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Finiquito $record) => route('finiquitos.pdf', $record))
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
