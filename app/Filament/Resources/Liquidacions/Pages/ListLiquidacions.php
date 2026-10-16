<?php

namespace App\Filament\Resources\Liquidacions\Pages;

use App\Filament\Resources\Liquidacions\LiquidacionResource;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\RemuneracionService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLiquidacions extends ListRecords
{
    protected static string $resource = LiquidacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generar_mensual')
                ->label('Generar Liquidación Mensual')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->modalHeading('Calcular Liquidaciones de Sueldo')
                ->modalDescription('Genera y calcula automáticamente las liquidaciones mensuales con la normativa legal chilena (Gratificación legal, Fonasa/Isapre, AFP y Seguro de Cesantía).')
                ->form([
                    Select::make('trabajador_id')
                        ->label('Trabajador')
                        ->placeholder('Todos los trabajadores con contrato activo')
                        ->options(Trabajador::all()->mapWithKeys(fn ($t) => [$t->id => "{$t->rut} - {$t->nombres} {$t->apellidos}"]))
                        ->searchable()
                        ->nullable()
                        ->helperText('Deja vacío para generar las liquidaciones de todo el personal a la vez.'),

                    Select::make('mes')
                        ->label('Mes')
                        ->options([
                            1 => 'Enero',
                            2 => 'Febrero',
                            3 => 'Marzo',
                            4 => 'Abril',
                            5 => 'Mayo',
                            6 => 'Junio',
                            7 => 'Julio',
                            8 => 'Agosto',
                            9 => 'Septiembre',
                            10 => 'Octubre',
                            11 => 'Noviembre',
                            12 => 'Diciembre',
                        ])
                        ->default(now()->month)
                        ->required(),

                    TextInput::make('anio')
                        ->label('Año')
                        ->numeric()
                        ->default(now()->year)
                        ->required(),

                    TextInput::make('dias_trabajados')
                        ->label('Días Trabajados')
                        ->numeric()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(30)
                        ->required()
                        ->helperText('Estándar legal chileno de 30 días para mes completo.'),
                ])
                ->action(function (array $data) {
                    $mes = (int) $data['mes'];
                    $anio = (int) $data['anio'];
                    $diasTrabajados = (int) $data['dias_trabajados'];
                    $trabajadorId = $data['trabajador_id'] ?? null;

                    // Asegurar parámetros legales para el período
                    ParametroLegal::firstOrCreate(
                        ['mes' => $mes, 'anio' => $anio],
                        [
                            'valor_uf' => 38000,
                            'valor_utm' => 67000,
                            'sueldo_minimo' => 500000,
                            'tope_imponible_afp' => 84.3,
                            'tope_imponible_ips' => 60.0,
                            'tope_imponible_cesantia' => 126.6,
                            'porcentaje_sis' => 1.49,
                        ]
                    );

                    $query = Trabajador::query();
                    if ($trabajadorId) {
                        $query->where('id', $trabajadorId);
                    }
                    $trabajadores = $query->get();

                    if ($trabajadores->isEmpty()) {
                        Notification::make()
                            ->title('No hay trabajadores registrados en el sistema.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $service = new RemuneracionService();
                    $generadas = 0;
                    $sinContrato = [];

                    foreach ($trabajadores as $trabajador) {
                        if (!$trabajador->contratos()->exists()) {
                            $sinContrato[] = "{$trabajador->nombres} {$trabajador->apellidos}";
                            continue;
                        }

                        $service->calcularLiquidacion($trabajador, $mes, $anio, $diasTrabajados);
                        $generadas++;
                    }

                    if ($generadas > 0) {
                        Notification::make()
                            ->title("Se generaron {$generadas} liquidación(es) para el período " . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$anio}.")
                            ->success()
                            ->send();
                    }

                    if (count($sinContrato) > 0) {
                        Notification::make()
                            ->title('Trabajadores sin contrato registrado')
                            ->body('No se pudo calcular para: ' . implode(', ', $sinContrato) . '. Deben tener un contrato registrado con sueldo base.')
                            ->warning()
                            ->send();
                    }
                }),

            CreateAction::make()->label('Nueva Liquidación Manual'),
        ];
    }
}

