<?php

namespace App\Filament\Resources\Turnos\Schemas;

use Filament\Schemas\Schema;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;

class TurnoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles del Turno')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        Select::make('trabajador_id')
                            ->label('Trabajador')
                            ->relationship('trabajador', 'rut')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->rut} - {$record->nombres} {$record->apellidos}")
                            ->searchable(['rut', 'nombres', 'apellidos'])
                            ->required()
                            ->columnSpan('full'),
                        Select::make('tipo_turno')
                            ->label('Tipo de Turno')
                            ->options([
                                'Dia' => 'Día (08:00 a 20:00)',
                                'Noche' => 'Noche (20:00 a 08:00)',
                                'Cuarto Turno' => 'Cuarto Turno (Largo, Noche, Libre, Libre)',
                                'Libre' => 'Día Libre',
                                'Vacaciones' => 'Vacaciones',
                                'Licencia Medica' => 'Licencia Médica',
                            ])
                            ->default('Dia')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $inicio = $get('fecha_inicio');
                                $base = $inicio ? \Carbon\Carbon::parse($inicio) : \Carbon\Carbon::today();

                                if ($state === 'Dia') {
                                    $set('fecha_inicio', $base->copy()->setTime(8, 0)->format('Y-m-d H:i'));
                                    $set('fecha_fin', $base->copy()->setTime(20, 0)->format('Y-m-d H:i'));
                                } elseif ($state === 'Noche') {
                                    $set('fecha_inicio', $base->copy()->setTime(20, 0)->format('Y-m-d H:i'));
                                    $set('fecha_fin', $base->copy()->addDay()->setTime(8, 0)->format('Y-m-d H:i'));
                                }
                            })
                            ->required(),

                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'Programado' => 'Programado',
                                'Completado' => 'Asistió (Completado)',
                                'Ausente' => 'Ausente (Faltó)',
                            ])
                            ->default('Programado')
                            ->required(),

                        DateTimePicker::make('fecha_inicio')
                            ->label('Inicio del Turno')
                            ->default(fn () => now()->setTime(8, 0))
                            ->seconds(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (! $state) return;
                                $tipo = $get('tipo_turno');
                                $base = \Carbon\Carbon::parse($state);

                                if ($tipo === 'Dia') {
                                    $set('fecha_inicio', $base->copy()->setTime(8, 0)->format('Y-m-d H:i'));
                                    $set('fecha_fin', $base->copy()->setTime(20, 0)->format('Y-m-d H:i'));
                                } elseif ($tipo === 'Noche') {
                                    $set('fecha_inicio', $base->copy()->setTime(20, 0)->format('Y-m-d H:i'));
                                    $set('fecha_fin', $base->copy()->addDay()->setTime(8, 0)->format('Y-m-d H:i'));
                                }
                            })
                            ->required()
                            ->rule(function (callable $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $trabajadorId = $get('trabajador_id');
                                    $fechaFin = $get('fecha_fin');
                                    if (!$trabajadorId || !$fechaFin) return;

                                    $overlapping = \App\Models\Turno::where('trabajador_id', $trabajadorId)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->where(function ($query) use ($value, $fechaFin) {
                                            $query->whereBetween('fecha_inicio', [$value, $fechaFin])
                                                  ->orWhereBetween('fecha_fin', [$value, $fechaFin])
                                                  ->orWhere(function ($q) use ($value, $fechaFin) {
                                                      $q->where('fecha_inicio', '<=', $value)
                                                        ->where('fecha_fin', '>=', $fechaFin);
                                                  });
                                        })->exists();

                                    if ($overlapping) {
                                        $fail('⚠️ Choque de horario: El trabajador ya tiene un turno asignado en esas horas.');
                                    }
                                };
                            }),

                        DateTimePicker::make('fecha_fin')
                            ->label('Fin del Turno')
                            ->default(fn () => now()->setTime(20, 0))
                            ->seconds(false)
                            ->required(),

                        Textarea::make('observaciones')
                            ->label('Observaciones Adicionales')
                            ->columnSpan('full'),
                    ]),
            ]);
    }
}
