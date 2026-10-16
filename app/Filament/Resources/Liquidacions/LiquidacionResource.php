<?php

namespace App\Filament\Resources\Liquidacions;

use App\Filament\Resources\Liquidacions\Pages\CreateLiquidacion;
use App\Filament\Resources\Liquidacions\Pages\EditLiquidacion;
use App\Filament\Resources\Liquidacions\Pages\ListLiquidacions;
use App\Filament\Resources\Liquidacions\Schemas\LiquidacionForm;
use App\Filament\Resources\Liquidacions\Tables\LiquidacionsTable;
use App\Models\Liquidacion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LiquidacionResource extends Resource
{
    protected static ?string $model = Liquidacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LiquidacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LiquidacionsTable::configure($table);
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
            'index' => ListLiquidacions::route('/'),
            'create' => CreateLiquidacion::route('/create'),
            'edit' => EditLiquidacion::route('/{record}/edit'),
        ];
    }
}
