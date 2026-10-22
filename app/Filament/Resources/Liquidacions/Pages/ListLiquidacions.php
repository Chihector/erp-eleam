<?php

namespace App\Filament\Resources\Liquidacions\Pages;

use App\Filament\Resources\Liquidacions\LiquidacionResource;
use App\Filament\Support\SelectTrabajador;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\LibroRemuneracionesService;
use App\Services\RemuneracionService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

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
                ->modalDescription('Genera un borrador de liquidaciones con los parámetros y datos previsionales registrados. La verificación de UF, UTM, sueldo mínimo, topes y SIS es responsabilidad de una persona antes de pagar.')
                ->form([
                    SelectTrabajador::make()
                        ->placeholder('Todos los trabajadores con contrato activo')
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

                    Select::make('origen_dias')
                        ->label('Días Trabajados')
                        ->options([
                            'licencias' => 'Mes completo menos licencias médicas',
                            'asistencia' => 'Según asistencias registradas',
                            'manual' => 'Ingresar manualmente',
                        ])
                        ->default('licencias')
                        ->live()
                        ->required()
                        ->helperText('El modo automático descuenta las licencias no rechazadas y, si se elige, cuenta los días con marca de entrada.'),

                    TextInput::make('dias_trabajados')
                        ->label('Días (modo manual)')
                        ->numeric()
                        ->default(30)
                        ->minValue(0)
                        ->maxValue(30)
                        ->required(fn (Get $get): bool => $get('origen_dias') === 'manual')
                        ->visible(fn (Get $get): bool => $get('origen_dias') === 'manual')
                        ->helperText('Estándar legal chileno de 30 días para mes completo.'),
                ])
                ->action(function (array $data) {
                    $mes = (int) $data['mes'];
                    $anio = (int) $data['anio'];
                    $origenDias = (string) ($data['origen_dias'] ?? 'licencias');
                    $diasTrabajados = $origenDias === 'manual'
                        ? (int) ($data['dias_trabajados'] ?? 30)
                        : null;
                    $trabajadorId = $data['trabajador_id'] ?? null;

                    // No se inventan parámetros: deben existir y estar revisados.
                    $parametro = ParametroLegal::query()
                        ->where('mes', $mes)
                        ->where('anio', $anio)
                        ->first();

                    if (! $parametro) {
                        Notification::make()
                            ->title('Faltan los parámetros legales del período '.str_pad($mes, 2, '0', STR_PAD_LEFT)."/{$anio}.")
                            ->body('Ve a Configuración > Parámetros Legales y usa "Actualizar desde mindicador.cl" antes de generar liquidaciones.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! $parametro->estaVerificado()) {
                        Notification::make()
                            ->title('Los parámetros del período aún no están verificados.')
                            ->body('Puedes generar un borrador, pero un responsable debe revisar UF, UTM, sueldo mínimo, topes y SIS antes de pagar.')
                            ->warning()
                            ->send();
                    }

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

                    $service = new RemuneracionService;
                    $generadas = 0;
                    $errores = [];

                    foreach ($trabajadores as $trabajador) {
                        try {
                            $service->calcularLiquidacion($trabajador, $mes, $anio, $diasTrabajados, [
                                'origen_dias' => $origenDias,
                            ]);
                            $generadas++;
                        } catch (\Throwable $e) {
                            $errores[] = "{$trabajador->nombres} {$trabajador->apellidos}: {$e->getMessage()}";
                        }
                    }

                    if ($generadas > 0) {
                        Notification::make()
                            ->title("Se generaron {$generadas} liquidación(es) para el período ".str_pad($mes, 2, '0', STR_PAD_LEFT)."/{$anio}.")
                            ->success()
                            ->send();
                    }

                    if (count($errores) > 0) {
                        Notification::make()
                            ->title('Trabajadores que no se pudieron liquidar')
                            ->body(implode(' · ', $errores))
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                }),

            CreateAction::make()->label('Nueva Liquidación Manual'),

            Action::make('libro_dt')
                ->label('Libro de Remuneraciones DT')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Descargar Libro de Remuneraciones Digital')
                ->modalDescription('Genera el archivo CSV con las remuneraciones del período para respaldo y revisión (formato simplificado para la Dirección del Trabajo).')
                ->modalSubmitActionLabel('Descargar CSV')
                ->form([
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
                ])
                ->action(function (array $data) {
                    $mes = (int) $data['mes'];
                    $anio = (int) $data['anio'];

                    $servicio = app(LibroRemuneracionesService::class);

                    if ($servicio->cantidadDelPeriodo($mes, $anio) === 0) {
                        Notification::make()
                            ->title('No hay liquidaciones en el período '.str_pad($mes, 2, '0', STR_PAD_LEFT)."/{$anio}.")
                            ->body('Primero genera las liquidaciones del mes y luego vuelve a descargar el libro.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    $csv = $servicio->generarCsv($mes, $anio);
                    $nombreArchivo = 'libro-remuneraciones-'.str_pad($mes, 2, '0', STR_PAD_LEFT)."-{$anio}.csv";

                    return response()->streamDownload(
                        fn () => print ($csv),
                        $nombreArchivo,
                        ['Content-Type' => 'text/csv; charset=UTF-8'],
                    );
                }),
        ];
    }
}
