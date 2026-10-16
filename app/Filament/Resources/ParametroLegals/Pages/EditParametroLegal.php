<?php

namespace App\Filament\Resources\ParametroLegals\Pages;

use App\Filament\Resources\ParametroLegals\ParametroLegalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditParametroLegal extends EditRecord
{
    protected static string $resource = ParametroLegalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
