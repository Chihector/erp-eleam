<?php

namespace App\Filament\Resources\Finiquitos\Pages;

use App\Filament\Resources\Finiquitos\FiniquitoResource;
use App\Filament\Support\SelectTrabajador;
use App\Models\Finiquito;
use App\Models\Trabajador;
use App\Services\FiniquitoService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListFiniquitos extends ListRecords
{
    protected static string $resource = FiniquitoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calcular_finiquito')
                ->label('Calcular Finiquito')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->modalHeading('Calcular Finiquito')
                ->modalDescription('El sistema calcula automáticamente las indemnizaciones, vacaciones pendientes y gratificación según la normativa chilena.')
                ->modalSubmitActionLabel('Calcular y Guardar')
                ->form([
                    SelectTrabajador::make()
                        ->required(),

                    Select::make('causal')
                        ->label('Causal del Término')
                        ->options([
                            'Mutuo Acuerdo (Art. 159 N°1)' => 'Mutuo Acuerdo (Art. 159 N°1)',
                            'Término de Plazo Fijo (Art. 159 N°4)' => 'Término de Plazo Fijo (Art. 159 N°4)',
                            'Necesidades de la Empresa (Art. 161)' => 'Necesidades de la Empresa (Art. 161)',
                            'Incumplimiento Grave (Art. 160)' => 'Incumplimiento Grave (Art. 160)',
                            'Renuncia Voluntaria' => 'Renuncia Voluntaria',
                        ])
                        ->required(),

                    DatePicker::make('fecha_termino')
                        ->label('Fecha de Término')
                        ->default(now())
                        ->required()
                        ->displayFormat('d/m/Y'),

                    Textarea::make('observaciones')
                        ->label('Observaciones'),
                ])
                ->action(function (array $data): void {
                    $trabajador = Trabajador::findOrFail($data['trabajador_id']);

                    try {
                        $calculo = app(FiniquitoService::class)->calcular(
                            $trabajador,
                            $data['causal'],
                            Carbon::parse($data['fecha_termino']),
                        );
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('No se pudo calcular el finiquito')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $finiquito = Finiquito::create(array_merge($calculo, [
                        'trabajador_id' => $trabajador->id,
                        'causal' => $data['causal'],
                        'fecha_termino' => $data['fecha_termino'],
                        'estado' => 'Borrador',
                        'observaciones' => $data['observaciones'] ?? null,
                    ]));

                    Notification::make()
                        ->title('Finiquito calculado y guardado')
                        ->body("Revisa el detalle y descarga el PDF del finiquito de {$trabajador->nombres} {$trabajador->apellidos}.")
                        ->success()
                        ->send();

                    $this->redirect(FiniquitoResource::getUrl('edit', ['record' => $finiquito]));
                }),
            CreateAction::make(),
        ];
    }
}
