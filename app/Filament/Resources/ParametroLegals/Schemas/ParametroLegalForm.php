<?php

namespace App\Filament\Resources\ParametroLegals\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ParametroLegalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('mes')
                    ->required()
                    ->numeric(),
                TextInput::make('anio')
                    ->required()
                    ->numeric(),
                TextInput::make('valor_uf')
                    ->required()
                    ->numeric(),
                TextInput::make('valor_utm')
                    ->required()
                    ->numeric(),
                TextInput::make('sueldo_minimo')
                    ->required()
                    ->numeric(),
                TextInput::make('tope_imponible_afp')
                    ->required()
                    ->numeric(),
                TextInput::make('tope_imponible_ips')
                    ->required()
                    ->numeric(),
                TextInput::make('tope_imponible_cesantia')
                    ->required()
                    ->numeric(),
                TextInput::make('porcentaje_sis')
                    ->required()
                    ->numeric()
                    ->default(1.54),
            ]);
    }
}
