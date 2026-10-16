<?php

namespace App\Filament\Resources\Trabajadors\Pages;

use App\Filament\Resources\Trabajadors\TrabajadorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrabajador extends EditRecord
{
    protected static string $resource = TrabajadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
