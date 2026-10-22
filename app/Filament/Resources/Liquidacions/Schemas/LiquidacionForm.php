<?php

namespace App\Filament\Resources\Liquidacions\Schemas;

use App\Filament\Support\SelectTrabajador;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LiquidacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Trabajador y Período')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        SelectTrabajador::make()
                            ->required(),

                        Select::make('contrato_id')
                            ->label('Contrato Asignado')
                            ->relationship('contrato', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Contrato #{$record->id} ($".number_format($record->sueldo_base, 0, ',', '.').')')
                            ->required(),

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
                            ->required(),

                        TextInput::make('anio')
                            ->label('Año')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),

                        TextInput::make('dias_trabajados')
                            ->label('Días Trabajados')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(30)
                            ->default(30)
                            ->required(),

                        Select::make('origen_dias')
                            ->label('Origen de los Días')
                            ->options([
                                'manual' => 'Ingresados manualmente',
                                'licencias' => 'Mes completo menos licencias',
                                'asistencia' => 'Según asistencias registradas',
                            ])
                            ->default('manual')
                            ->required(),

                        Toggle::make('cerrada')
                            ->label('Liquidación Cerrada')
                            ->default(false)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('El cierre y la reapertura se realizan con las acciones del listado.'),
                    ]),

                Section::make('Haberes Imponibles')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('sueldo_base')
                            ->label('Sueldo Base')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('gratificacion')
                            ->label('Gratificación Legal')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('total_imponible')
                            ->label('Total Imponible')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('renta_liquida_imponible')
                            ->label('Renta Líquida Imponible')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Total imponible menos AFP, salud y cesantía. Base del IUSC.'),
                    ]),

                Section::make('Haberes No Imponibles')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('monto_colacion')
                            ->label('Colación')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('monto_movilizacion')
                            ->label('Movilización')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('cargas_familiares')
                            ->label('Cargas Familiares')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('total_no_imponible')
                            ->label('Total No Imponible')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('total_haberes')
                            ->label('Total Haberes')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ]),

                Section::make('Descuentos Previsionales y de Salud')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('afp_nombre')
                            ->label('AFP')
                            ->required(),

                        TextInput::make('afp_porcentaje')
                            ->label('% AFP')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        TextInput::make('monto_afp')
                            ->label('Monto AFP')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('salud_nombre')
                            ->label('Institución Salud')
                            ->required(),

                        TextInput::make('salud_porcentaje')
                            ->label('% Salud')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        TextInput::make('monto_salud')
                            ->label('Monto Salud')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('monto_cesantia')
                            ->label('Seguro Cesantía')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('impuesto_segunda_categoria')
                            ->label('Impuesto Único 2ª Categoría')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Se calcula sobre la renta líquida imponible con la tabla SII del período.'),

                        TextInput::make('total_descuentos_legales')
                            ->label('Total Descuentos Legales')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->helperText('Incluye AFP, salud, cesantía e impuesto único.'),
                    ]),

                Section::make('Totales Finales')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('descuento_anticipo')
                            ->label('Anticipo')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('descuento_sindicato')
                            ->label('Cuota Sindical')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),

                        TextInput::make('otros_descuentos')
                            ->label('Otros Descuentos (total)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required()
                            ->helperText('Incluye anticipo, cuota sindical y otros descuentos varios.'),

                        TextInput::make('total_descuentos')
                            ->label('Total Descuentos')
                            ->numeric()
                            ->prefix('$')
                            ->required(),

                        TextInput::make('sueldo_liquido')
                            ->label('Sueldo Líquido a Pagar')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ]),
            ]);
    }
}
