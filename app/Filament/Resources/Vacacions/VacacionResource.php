<?php

namespace App\Filament\Resources\Vacacions;

use App\Filament\Resources\Vacacions\Pages\CreateVacacion;
use App\Filament\Resources\Vacacions\Pages\EditVacacion;
use App\Filament\Resources\Vacacions\Pages\ListVacacions;
use App\Filament\Resources\Vacacions\Schemas\VacacionForm;
use App\Filament\Resources\Vacacions\Tables\VacacionsTable;
use App\Models\Vacacion;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacacionResource extends Resource
{
    protected static ?string $model = Vacacion::class;

    protected static ?string $modelLabel = 'Solicitud de Vacaciones';
    protected static ?string $pluralModelLabel = 'Vacaciones';
    protected static ?string $navigationLabel = 'Vacaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return VacacionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VacacionsTable::configure($table);
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
            'index' => ListVacacions::route('/'),
            'create' => CreateVacacion::route('/create'),
            'edit' => EditVacacion::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(User::ROLES_PANEL) ?? false;
    }
}
