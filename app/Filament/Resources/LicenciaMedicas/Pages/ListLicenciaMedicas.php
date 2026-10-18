<?php

namespace App\Filament\Resources\LicenciaMedicas\Pages;

use App\Filament\Resources\LicenciaMedicas\LicenciaMedicaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLicenciaMedicas extends ListRecords
{
    protected static string $resource = LicenciaMedicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
