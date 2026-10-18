<?php

namespace App\Filament\Resources\LicenciaMedicas\Pages;

use App\Filament\Resources\LicenciaMedicas\LicenciaMedicaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLicenciaMedica extends EditRecord
{
    protected static string $resource = LicenciaMedicaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
