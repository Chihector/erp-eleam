<?php

namespace App\Filament\Resources\ParametroLegals\Pages;

use App\Filament\Resources\ParametroLegals\ParametroLegalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParametroLegals extends ListRecords
{
    protected static string $resource = ParametroLegalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
