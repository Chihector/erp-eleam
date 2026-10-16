<?php

namespace App\Filament\Resources\Gastos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class GastoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->label('Fecha del Gasto')
                    ->required(),
                Select::make('categoria')
                    ->label('Categoría')
                    ->options([
                        'Luz' => 'Luz',
                        'Agua' => 'Agua',
                        'Gas' => 'Gas',
                        'Comida' => 'Comida',
                        'Insumos' => 'Insumos',
                        'Servicios' => 'Servicios',
                        'Otros' => 'Otros',
                    ])
                    ->searchable()
                    ->required(),
                Textarea::make('descripcion')
                    ->label('Descripción detallada')
                    ->columnSpanFull(),
                TextInput::make('monto')
                    ->label('Monto total (CLP)')
                    ->prefix('$')
                    ->required()
                    ->numeric(),
                FileUpload::make('comprobante')
                    ->label('Comprobante (Boleta/Factura)')
                    ->directory('comprobantes_gastos'),
            ]);
    }
}
