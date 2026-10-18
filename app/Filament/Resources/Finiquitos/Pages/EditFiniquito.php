<?php

namespace App\Filament\Resources\Finiquitos\Pages;

use App\Filament\Resources\Finiquitos\FiniquitoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFiniquito extends EditRecord
{
    protected static string $resource = FiniquitoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
