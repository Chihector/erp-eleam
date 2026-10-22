<?php

namespace App\Filament\Resources\ParametroLegals\Pages;

use App\Filament\Resources\ParametroLegals\ParametroLegalResource;
use App\Services\ParametroLegalService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListParametroLegals extends ListRecords
{
    protected static string $resource = ParametroLegalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('actualizar_mindicador')
                ->label('Actualizar desde mindicador.cl')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->modalHeading('Actualizar parámetros legales')
                ->modalDescription('Se consultarán la UF y la UTM del período. El sueldo mínimo se toma de la tabla de vigencia o del valor que indiques. Los valores quedan SIN VERIFICAR hasta que una persona los revise.')
                ->modalSubmitActionLabel('Consultar y guardar')
                ->form([
                    Select::make('mes')
                        ->label('Mes')
                        ->options([
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                        ])
                        ->default(now()->month)
                        ->required(),

                    TextInput::make('anio')
                        ->label('Año')
                        ->numeric()
                        ->default(now()->year)
                        ->required(),

                    TextInput::make('sueldo_minimo')
                        ->label('Sueldo mínimo (opcional)')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Déjalo vacío para usar el valor configurado según la fecha de vigencia.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $parametro = app(ParametroLegalService::class)->actualizar(
                            (int) $data['mes'],
                            (int) $data['anio'],
                            filled($data['sueldo_minimo'] ?? null) ? (int) $data['sueldo_minimo'] : null,
                        );
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('No se pudieron actualizar los parámetros')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Parámetros actualizados: '.str_pad((string) $parametro->mes, 2, '0', STR_PAD_LEFT)."/{$parametro->anio}")
                        ->body(
                            'UF $'.number_format($parametro->valor_uf, 2, ',', '.')
                            .' · UTM $'.number_format($parametro->valor_utm, 0, ',', '.')
                            .' · Sueldo mínimo $'.number_format($parametro->sueldo_minimo, 0, ',', '.')
                            .'. Queda pendiente la verificación humana.'
                        )
                        ->warning()
                        ->persistent()
                        ->send();
                }),

            CreateAction::make()->label('Nuevo Período Manual'),
        ];
    }
}
