<?php

namespace App\Filament\Resources\Trabajadors\Pages;

use App\Filament\Resources\Trabajadors\TrabajadorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrabajadors extends ListRecords
{
    protected static string $resource = TrabajadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
