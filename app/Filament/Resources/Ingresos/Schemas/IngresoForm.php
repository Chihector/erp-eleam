<?php

namespace App\Filament\Resources\Ingresos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IngresoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('fecha')
                    ->required(),
                TextInput::make('categoria')
                    ->required(),
                Textarea::make('descripcion')
                    ->columnSpanFull(),
                TextInput::make('monto')
                    ->required()
                    ->numeric(),
                TextInput::make('comprobante'),
            ]);
    }
}
