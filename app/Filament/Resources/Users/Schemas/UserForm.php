<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Form; // Use form for proper filament v3 typing if needed, but it's Schema in this custom setup apparently
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use App\Rules\RutChile;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de Usuario')
                    ->description('Información personal y credenciales de acceso.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombres')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('apellido')
                                    ->label('Apellidos')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        
                        TextInput::make('rut')
                            ->label('RUT')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rule(new RutChile())
                            ->placeholder('12345678-9'),

                        TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->revealable()
                                    ->confirmed()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create'),
                                TextInput::make('password_confirmation')
                                    ->label('Confirmar Contraseña')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->required(fn (string $context): bool => $context === 'create'),
                            ]),
                    ])
            ]);
    }
}
