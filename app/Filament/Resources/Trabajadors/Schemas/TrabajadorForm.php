<?php

namespace App\Filament\Resources\Trabajadors\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\ColorPicker;
use App\Rules\RutChile;

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
                            ]),
                        Select::make('sistema_salud')
                            ->label('Sistema de Salud')
                            ->options([
                                'fonasa' => 'Fonasa',
                                'isapre' => 'Isapre',
                            ]),
                    ]),
            ]);
    }
}
