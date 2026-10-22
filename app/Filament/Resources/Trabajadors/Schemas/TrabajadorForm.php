<?php

namespace App\Filament\Resources\Trabajadors\Schemas;

use App\Rules\RutChile;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TrabajadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles Personales')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('rut')
                            ->label('RUT')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rule(new RutChile),
                        TextInput::make('fecha_ingreso')
                            ->label('Fecha de Ingreso')
                            ->type('date')
                            ->rule('before_or_equal:today')
                            ->required(),
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required(),
                        TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required(),
                        TextInput::make('cargo')
                            ->label('Cargo')
                            ->required(),
                        ColorPicker::make('color_calendario')
                            ->label('Color en Calendario')
                            ->default('#3b82f6')
                            ->required(),
                        Select::make('afp')
                            ->label('AFP')
                            ->options([
                                'habitat' => 'Habitat',
                                'provida' => 'Provida',
                                'capital' => 'Capital',
                                'cuprum' => 'Cuprum',
                                'planvital' => 'PlanVital',
                                'modelo' => 'Modelo',
                                'uno' => 'Uno',
                            ])
                            ->required(),
                        TextInput::make('afp_tasa')
                            ->label('Tasa AFP (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(10)
                            ->maxValue(20)
                            ->helperText('10% obligatorio + comisión vigente de la AFP. Si se deja vacío se usa la tasa de referencia configurada.'),
                        Select::make('sistema_salud')
                            ->label('Sistema de Salud')
                            ->options([
                                'fonasa' => 'Fonasa',
                                'isapre' => 'Isapre',
                            ])
                            ->required()
                            ->live(),
                        TextInput::make('salud_plan_uf')
                            ->label('Plan Isapre (UF)')
                            ->numeric()
                            ->suffix('UF')
                            ->step(0.01)
                            ->visible(fn (Get $get): bool => $get('sistema_salud') === 'isapre')
                            ->required(fn (Get $get): bool => $get('sistema_salud') === 'isapre')
                            ->helperText('Plan contratado en UF. Se descuenta el mayor valor entre el plan y el 7% legal.'),
                    ]),
            ]);
    }
}
