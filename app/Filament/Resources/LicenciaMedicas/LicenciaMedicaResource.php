<?php

namespace App\Filament\Resources\LicenciaMedicas;

use App\Filament\Resources\LicenciaMedicas\Pages\CreateLicenciaMedica;
use App\Filament\Resources\LicenciaMedicas\Pages\EditLicenciaMedica;
use App\Filament\Resources\LicenciaMedicas\Pages\ListLicenciaMedicas;
use App\Filament\Resources\LicenciaMedicas\Schemas\LicenciaMedicaForm;
use App\Filament\Resources\LicenciaMedicas\Tables\LicenciasMedicasTable;
use App\Models\LicenciaMedica;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LicenciaMedicaResource extends Resource
{
    protected static ?string $model = LicenciaMedica::class;

    protected static ?string $modelLabel = 'Licencia Médica';

    protected static ?string $pluralModelLabel = 'Licencias Médicas';

    protected static ?string $navigationLabel = 'Licencias Médicas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return LicenciaMedicaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenciasMedicasTable::configure($table);
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
            'index' => ListLicenciaMedicas::route('/'),
            'create' => CreateLicenciaMedica::route('/create'),
            'edit' => EditLicenciaMedica::route('/{record}/edit'),
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
