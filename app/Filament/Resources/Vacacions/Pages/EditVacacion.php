<?php

namespace App\Filament\Resources\Vacacions\Pages;

use App\Filament\Resources\Vacacions\VacacionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditVacacion extends EditRecord
{
    protected static string $resource = VacacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
