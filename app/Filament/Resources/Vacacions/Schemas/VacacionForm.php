<?php

namespace App\Filament\Resources\Vacacions\Schemas;

use App\Filament\Support\SelectTrabajador;
use App\Models\Trabajador;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VacacionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la Solicitud')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        SelectTrabajador::make()
                            ->required()
                            ->reactive() // Make it reactive so we can update the placeholder
                            ->columnSpan('full'),
                        Placeholder::make('saldo_vacaciones')
                            ->label('Saldo de Vacaciones Disponible (Días)')
                            ->content(function (callable $get) {
                                $trabajador = Trabajador::find($get('trabajador_id'));
                                if (! $trabajador) {
                                    return '-';
                                }

                                return $trabajador->saldo_vacaciones;
                            }),
                        Select::make('estado')
                            ->label('Estado de Solicitud')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Aprobada' => 'Aprobada',
                                'Rechazada' => 'Rechazada',
                            ])
                            ->default('Pendiente')
                            ->required(),
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required(),
                        DatePicker::make('fecha_fin')
                            ->label('Fecha de Término')
                            ->required(),
                        TextInput::make('dias_habiles')
                            ->label('Cantidad de Días Hábiles Solicitados')
                            ->numeric()
                            ->required(),
                        Textarea::make('observaciones')
                            ->label('Observaciones (Opcional)')
                            ->columnSpan('full'),
                    ]),
            ]);
    }
}
