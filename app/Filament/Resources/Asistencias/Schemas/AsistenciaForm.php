<?php

namespace App\Filament\Resources\Asistencias\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

class AsistenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registro de Asistencia (Reloj Control)')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        Select::make('trabajador_id')
                            ->label('Trabajador')
                            ->relationship('trabajador', 'rut')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->rut} - {$record->nombres} {$record->apellidos}")
                            ->searchable(['rut', 'nombres', 'apellidos'])
                            ->required(),
                        Select::make('turno_id')
                            ->label('Turno Planificado (Opcional)')
                            ->relationship('turno', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Turno {$record->tipo_turno} ({$record->fecha_inicio})")
                            ->searchable(),
                        DateTimePicker::make('fecha_hora_entrada')
                            ->label('Hora de Entrada (Real)')
                            ->required()
                            ->default(now()),
                        DateTimePicker::make('fecha_hora_salida')
                            ->label('Hora de Salida (Real)')
                            ->default(null),
                        Textarea::make('observaciones')
                            ->label('Observaciones (Ej: Llegó tarde por x motivo)')
                            ->columnSpan('full'),
                    ])
            ]);
    }
}
