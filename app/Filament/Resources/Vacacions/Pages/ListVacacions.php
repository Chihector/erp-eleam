<?php

namespace App\Filament\Resources\Vacacions\Pages;

use App\Filament\Resources\Vacacions\VacacionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVacacions extends ListRecords
{
    protected static string $resource = VacacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
