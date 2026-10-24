<?php

namespace App\Filament\Resources\Liquidacions\Pages;

use App\Filament\Resources\Liquidacions\LiquidacionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLiquidacion extends EditRecord
{
    protected static string $resource = LiquidacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Eliminar')
                ->modalHeading('Eliminar liquidación')
                ->modalDescription('La liquidación se archivará y podrá restaurarse. No se borra definitivamente.')
                ->successNotificationTitle('Liquidación archivada')
                ->authorize('delete'),
        ];
    }
}
