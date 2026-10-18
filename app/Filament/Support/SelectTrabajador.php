<?php

namespace App\Filament\Support;

use App\Models\Trabajador;
use App\Support\Buscador;
use Filament\Forms\Components\Select;

/**
 * Selector de trabajador reutilizable con búsqueda tolerante a tildes:
 * escribir "hector" sugiere "Héctor", "munoz" sugiere "Muñoz" y el RUT
 * se encuentra con o sin puntos y guión.
 *
 * Reemplaza el patrón repetido:
 *   Select::make('trabajador_id')
 *       ->relationship('trabajador', 'rut')
 *       ->getOptionLabelFromRecordUsing(...)
 *       ->searchable(['rut', 'nombres', 'apellidos'])
 */
class SelectTrabajador
{
    public static function make(string $name = 'trabajador_id', string $label = 'Trabajador'): Select
    {
        return Select::make($name)
            ->label($label)
            ->searchable()
            // Listado completo al abrir el selector (ordenado por apellido).
            ->options(fn (): array => Buscador::trabajadores(limite: 500))
            // Resultados al escribir: búsqueda normalizada sin tildes.
            ->getSearchResultsUsing(fn (string $search): array => Buscador::trabajadores($search))
            // Etiqueta del valor ya seleccionado (al editar un registro).
            ->getOptionLabelUsing(function (mixed $value): ?string {
                if (blank($value)) {
                    return null;
                }

                $trabajador = Trabajador::find($value);

                return $trabajador ? Buscador::etiqueta($trabajador) : null;
            });
    }
}
