<?php

namespace App\Filament\Resources\Asistencias\Schemas;

use App\Filament\Support\SelectTrabajador;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AsistenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registro de Asistencia (Reloj Control)')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        SelectTrabajador::make()
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
                    ]),
            ]);
    }
}
