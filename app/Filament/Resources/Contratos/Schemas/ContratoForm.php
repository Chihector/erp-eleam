<?php

namespace App\Filament\Resources\Contratos\Schemas;

use App\Filament\Support\SelectTrabajador;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContratoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles del Contrato')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        SelectTrabajador::make()
                            ->required()
                            ->columnSpan('full'),
                        Select::make('tipo_contrato')
                            ->label('Tipo de Contrato')
                            ->options([
                                'indefinido' => 'Indefinido',
                                'plazo_fijo' => 'Plazo Fijo',
                                'obra_faena' => 'Obra o Faena',
                            ])
                            ->required(),
                        TextInput::make('sueldo_base')
                            ->label('Sueldo Base (CLP sin decimales)')
                            ->numeric()
                            ->required(),
                        TextInput::make('horas_semanales')
                            ->label('Horas Semanales')
                            ->numeric()
                            ->required()
                            ->maxValue(45),
                        Toggle::make('jornada_excepcional')
                            ->label('¿Jornada Excepcional?'),
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required(),
                        DatePicker::make('fecha_termino')
                            ->label('Fecha de Término (si aplica)'),
                    ]),
            ]);
    }
}
