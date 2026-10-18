<?php

namespace App\Filament\Resources\LicenciaMedicas\Schemas;

use App\Filament\Support\SelectTrabajador;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenciaMedicaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Licencia Médica')
                    ->description('El sistema calcula automáticamente los días de reposo y el subsidio según la normativa chilena.')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        SelectTrabajador::make()
                            ->required(),

                        TextInput::make('folio')
                            ->label('N° de Folio')
                            ->placeholder('Ej: 123456789')
                            ->maxLength(20),

                        Select::make('tipo')
                            ->label('Tipo de Licencia')
                            ->options([
                                'Común' => 'Común (enfermedad o accidente no laboral)',
                                'Maternal' => 'Maternal (pre y post natal)',
                                'Accidente del Trabajo' => 'Accidente del Trabajo',
                                'Enfermedad Profesional' => 'Enfermedad Profesional',
                            ])
                            ->default('Común')
                            ->required()
                            ->live(),

                        Select::make('entidad_emisora')
                            ->label('Entidad Emisora')
                            ->options([
                                'Fonasa' => 'Fonasa',
                                'Isapre' => 'Isapre',
                                'Mutual de Seguridad' => 'Mutual de Seguridad',
                            ])
                            ->default('Fonasa')
                            ->required(),

                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio del Reposo')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('fecha_fin')
                            ->label('Fecha de Término del Reposo')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('fecha_inicio')
                            ->helperText('El subsidio se recalcula automáticamente al guardar.'),

                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Validada' => 'Validada',
                                'Rechazada' => 'Rechazada',
                            ])
                            ->default('Pendiente')
                            ->required(),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Ej: licencia presentada en oficina de partes...')
                            ->columnSpan('full'),
                    ]),
            ]);
    }
}
