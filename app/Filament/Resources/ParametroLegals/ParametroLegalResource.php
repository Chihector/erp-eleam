<?php

namespace App\Filament\Resources\ParametroLegals;

use App\Filament\Resources\ParametroLegals\Pages\CreateParametroLegal;
use App\Filament\Resources\ParametroLegals\Pages\EditParametroLegal;
use App\Filament\Resources\ParametroLegals\Pages\ListParametroLegals;
use App\Filament\Resources\ParametroLegals\Schemas\ParametroLegalForm;
use App\Filament\Resources\ParametroLegals\Tables\ParametroLegalsTable;
use App\Models\ParametroLegal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParametroLegalResource extends Resource
{
    protected static ?string $model = ParametroLegal::class;

    protected static ?string $modelLabel = 'Parámetro Legal';
    protected static ?string $pluralModelLabel = 'Parámetros Legales';
    protected static ?string $navigationLabel = 'Parámetros Legales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ParametroLegalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParametroLegalsTable::configure($table);
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
            'index' => ListParametroLegals::route('/'),
            'create' => CreateParametroLegal::route('/create'),
            'edit' => EditParametroLegal::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }
}
