<?php

namespace App\Filament\Resources\Finiquitos;

use App\Filament\Resources\Finiquitos\Pages\CreateFiniquito;
use App\Filament\Resources\Finiquitos\Pages\EditFiniquito;
use App\Filament\Resources\Finiquitos\Pages\ListFiniquitos;
use App\Filament\Resources\Finiquitos\Schemas\FiniquitoForm;
use App\Filament\Resources\Finiquitos\Tables\FiniquitosTable;
use App\Models\Finiquito;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FiniquitoResource extends Resource
{
    protected static ?string $model = Finiquito::class;

    protected static ?string $modelLabel = 'Finiquito';

    protected static ?string $pluralModelLabel = 'Finiquitos';

    protected static ?string $navigationLabel = 'Finiquitos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMinus;

    protected static string|\UnitEnum|null $navigationGroup = 'Finanzas';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return FiniquitoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FiniquitosTable::configure($table);
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
            'index' => ListFiniquitos::route('/'),
            'create' => CreateFiniquito::route('/create'),
            'edit' => EditFiniquito::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(User::ROLES_PANEL) ?? false;
    }
}
