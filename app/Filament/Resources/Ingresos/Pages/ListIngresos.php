<?php

namespace App\Filament\Resources\Ingresos\Pages;

use App\Filament\Resources\Ingresos\IngresoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIngresos extends ListRecords
{
    protected static string $resource = IngresoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
