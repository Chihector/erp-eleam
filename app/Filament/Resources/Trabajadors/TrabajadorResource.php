<?php

namespace App\Filament\Resources\Trabajadors;

use App\Filament\Resources\Trabajadors\Pages\CreateTrabajador;
use App\Filament\Resources\Trabajadors\Pages\EditTrabajador;
use App\Filament\Resources\Trabajadors\Pages\ListTrabajadors;
use App\Filament\Resources\Trabajadors\Schemas\TrabajadorForm;
use App\Filament\Resources\Trabajadors\Tables\TrabajadorsTable;
use App\Models\Trabajador;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TrabajadorResource extends Resource
{
    protected static ?string $model = Trabajador::class;

    protected static ?string $modelLabel = 'Trabajador';
    protected static ?string $pluralModelLabel = 'Trabajadores';
    protected static ?string $navigationLabel = 'Trabajadores';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TrabajadorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrabajadorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrabajadors::route('/'),
            'create' => CreateTrabajador::route('/create'),
            'edit' => EditTrabajador::route('/{record}/edit'),
        ];
    }
}
