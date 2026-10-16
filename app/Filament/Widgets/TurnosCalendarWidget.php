<?php

namespace App\Filament\Widgets;

use App\Models\Turno;
use Carbon\Carbon;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TurnosCalendarWidget extends FullCalendarWidget
{
    protected static ?int $sort = 2;

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'displayEventTime' => false,
            'eventDisplay' => 'block',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,listWeek,timeGridDay',
            ],
            'buttonText' => [
                'today' => 'Hoy',
                'dayGridMonth' => 'Mes',
                'listWeek' => 'Agenda',
                'timeGridDay' => 'Día',
            ],
            'height' => 'auto',
            'navLinks' => true,
            'dayMaxEvents' => 3,
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return Turno::query()
            ->with('trabajador')
            ->where('fecha_inicio', '>=', $fetchInfo['start'])
            ->where('fecha_fin', '<=', $fetchInfo['end'])
            ->get()
            ->map(function (Turno $turno) {
                $inicio = Carbon::parse($turno->fecha_inicio)->format('H:i');
                $fin = Carbon::parse($turno->fecha_fin)->format('H:i');

                $trabajador = $turno->trabajador;
                if ($trabajador) {
                    $nombreParts = explode(' ', trim($trabajador->nombres ?? ''));
                    $primerNombre = !empty($nombreParts[0]) ? ucfirst(mb_strtolower($nombreParts[0])) : 'Turno';

                    $apellidoParts = explode(' ', trim($trabajador->apellidos ?? ''));
                    $primerApellido = !empty($apellidoParts[0]) ? ucfirst(mb_strtolower($apellidoParts[0])) : '';
                    $inicialApellido = !empty($primerApellido) ? mb_substr($primerApellido, 0, 1) . '.' : '';

                    $nombreVisible = trim("{$primerNombre} {$inicialApellido}");
                } else {
                    $nombreVisible = 'Sin asignar';
                }

                // Colocamos primero el nombre e inicial del trabajador para que en móviles sea 100% visible
                $title = "{$nombreVisible} ({$inicio}-{$fin})";

                return EventData::make()
                    ->id((string) $turno->id)
                    ->title($title)
                    ->start($turno->fecha_inicio->toIso8601String())
                    ->end($turno->fecha_fin->toIso8601String())
                    ->backgroundColor($turno->trabajador?->color_calendario ?? '#3b82f6')
                    ->borderColor($turno->trabajador?->color_calendario ?? '#3b82f6')
                    ->toArray();
            })
            ->toArray();
    }
}

