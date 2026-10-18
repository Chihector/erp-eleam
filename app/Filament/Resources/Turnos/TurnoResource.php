<?php

namespace App\Filament\Resources\Turnos;

use App\Filament\Resources\Turnos\Pages\CreateTurno;
use App\Filament\Resources\Turnos\Pages\EditTurno;
use App\Filament\Resources\Turnos\Pages\ListTurnos;
use App\Filament\Resources\Turnos\Schemas\TurnoForm;
use App\Filament\Resources\Turnos\Tables\TurnosTable;
use App\Models\Turno;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TurnoResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static ?string $modelLabel = 'Turno';
    protected static ?string $pluralModelLabel = 'Turnos';
    protected static ?string $navigationLabel = 'Turnos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TurnoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TurnosTable::configure($table);
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
            'index' => ListTurnos::route('/'),
            'create' => CreateTurno::route('/create'),
            'edit' => EditTurno::route('/{record}/edit'),
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
