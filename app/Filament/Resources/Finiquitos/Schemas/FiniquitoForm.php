<?php

namespace App\Filament\Resources\Finiquitos\Schemas;

use App\Filament\Support\SelectTrabajador;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FiniquitoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del Término de Contrato')
                    ->description('Para el cálculo automático usa el botón "Calcular Finiquito" del listado.')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        SelectTrabajador::make()
                            ->required(),

                        DatePicker::make('fecha_termino')
                            ->label('Fecha de Término')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        Select::make('causal')
                            ->label('Causal del Término')
                            ->options([
                                'Mutuo Acuerdo (Art. 159 N°1)' => 'Mutuo Acuerdo (Art. 159 N°1)',
                                'Término de Plazo Fijo (Art. 159 N°4)' => 'Término de Plazo Fijo (Art. 159 N°4)',
                                'Necesidades de la Empresa (Art. 161)' => 'Necesidades de la Empresa (Art. 161)',
                                'Incumplimiento Grave (Art. 160)' => 'Incumplimiento Grave (Art. 160)',
                                'Renuncia Voluntaria' => 'Renuncia Voluntaria',
                            ])
                            ->required(),

                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'Borrador' => 'Borrador',
                                'Firmado' => 'Firmado',
                            ])
                            ->default('Borrador')
                            ->required(),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpan('full'),
                    ]),

                Section::make('Antigüedad y Sueldo de Cálculo')
                    ->columns(['sm' => 1, 'md' => 3])
                    ->schema([
                        TextInput::make('anios_servicio')
                            ->label('Años de Servicio')
                            ->numeric()
                            ->default(0),

                        TextInput::make('meses_servicio')
                            ->label('Meses Adicionales')
                            ->numeric()
                            ->default(0),

                        TextInput::make('sueldo_base_calculo')
                            ->label('Sueldo de Cálculo (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                    ]),

                Section::make('Haberes del Finiquito')
                    ->description('Se calculan automáticamente; puedes ajustarlos manualmente si es necesario.')
                    ->columns(['sm' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('indemnizacion_anios')
                            ->label('Indemnización por Años de Servicio (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        TextInput::make('indemnizacion_aviso_previo')
                            ->label('Indemnización Aviso Previo (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        TextInput::make('vacaciones_pendientes')
                            ->label('Vacaciones Pendientes (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        TextInput::make('gratificacion_pendiente')
                            ->label('Gratificación Proporcional (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        TextInput::make('total_haberes')
                            ->label('Total Haberes (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        TextInput::make('otros_descuentos')
                            ->label('Otros Descuentos (CLP)')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('El líquido a pagar se recalcula automáticamente al guardar.'),
                    ]),
            ]);
    }
}
