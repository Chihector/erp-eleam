<?php

namespace App\Filament\Resources\ParametroLegals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParametroLegalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Período y valores legales')
                    ->description('Estos valores se usan para calcular remuneraciones, licencias y finiquitos. Regístralos desde una fuente oficial y verifica el período antes de pagar.')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('mes')
                            ->label('Mes')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(12),
                        TextInput::make('anio')
                            ->label('Año')
                            ->required()
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100),
                        TextInput::make('valor_uf')
                            ->label('Valor UF')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                        TextInput::make('valor_utm')
                            ->label('Valor UTM')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('sueldo_minimo')
                            ->label('Sueldo mínimo')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('porcentaje_sis')
                            ->label('SIS (%)')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->default(1.49)
                            ->helperText('Aporte del empleador. No se descuenta del sueldo del trabajador.'),
                    ]),

                Section::make('Topes imponibles')
                    ->description('Expresados en UF. Confírmalos cuando cambie la normativa.')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('tope_imponible_afp')
                            ->label('Tope AFP (UF)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        TextInput::make('tope_imponible_ips')
                            ->label('Tope IPS (UF)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                        TextInput::make('tope_imponible_cesantia')
                            ->label('Tope Cesantía (UF)')
                            ->required()
                            ->numeric()
                            ->step(0.1),
                    ]),
            ]);
    }
}
