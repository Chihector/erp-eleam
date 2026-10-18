<?php

namespace App\Support;

use App\Models\Trabajador;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Búsqueda de texto en español tolerante a tildes y mayúsculas.
 *
 * Permite que "hector" encuentre "Héctor", "munoz" encuentre "Muñoz",
 * "dia" encuentre "Día" y que el RUT se busque con o sin puntos y guión
 * ("19.486.694-1" o "19486694" encuentran a "19486694-1").
 *
 * Se usa en dos contextos:
 *  - Selectores de trabajador de los formularios: `Buscador::trabajadores()`.
 *  - Columnas buscables de las tablas: `Buscador::columna()` y `Buscador::relacion()`.
 */
class Buscador
{
    /**
     * Reemplazos para quitar tildes, diéresis, la ñ y los separadores
     * de RUT. Un solo mapa compartido por la versión PHP y la versión SQL.
     *
     * @var array<string, string>
     */
    private const REEMPLAZOS = [
        'á' => 'a', 'Á' => 'a',
        'é' => 'e', 'É' => 'e',
        'í' => 'i', 'Í' => 'i',
        'ó' => 'o', 'Ó' => 'o',
        'ú' => 'u', 'Ú' => 'u',
        'ü' => 'u', 'Ü' => 'u',
        'ñ' => 'n', 'Ñ' => 'n',
        '.' => '', '-' => '',
    ];

    /**
     * Normaliza un texto o término de búsqueda: minúsculas, sin tildes
     * y sin puntos ni guiones (para que el RUT se busque de cualquier forma).
     */
    public static function normalizar(?string $texto): string
    {
        return strtr(mb_strtolower((string) $texto, 'UTF-8'), self::REEMPLAZOS);
    }

    /**
     * Expresión SQL portable (SQLite y PostgreSQL) equivalente a normalizar()
     * aplicada a una columna: minúsculas y sin tildes ni separadores de RUT.
     *
     * Se usan REPLACE anidados porque LOWER() de SQLite solo convierte
     * letras ASCII; las versiones en mayúscula con tilde se reemplazan aparte.
     *
     * Ejemplo: sqlNormalizado('nombres')
     *   => "REPLACE(REPLACE(LOWER(nombres), 'á', 'a'), ...)"
     */
    public static function sqlNormalizado(string $columna): string
    {
        $expresion = "LOWER({$columna})";

        foreach (self::REEMPLAZOS as $desde => $hasta) {
            $expresion = "REPLACE({$expresion}, '{$desde}', '{$hasta}')";
        }

        return $expresion;
    }

    /**
     * Etiqueta estándar de un trabajador en los selectores del sistema.
     */
    public static function etiqueta(Trabajador $trabajador): string
    {
        return "{$trabajador->rut} - {$trabajador->nombres} {$trabajador->apellidos}";
    }

    /**
     * Resultados de búsqueda de trabajadores para los selectores (id => etiqueta).
     *
     * Busca por RUT, nombres y apellidos sin distinguir tildes ni mayúsculas.
     * Con término vacío devuelve el listado completo ordenado por apellido.
     *
     * @return array<int, string>
     */
    public static function trabajadores(?string $termino = null, int $limite = 50): array
    {
        $buscado = self::normalizar($termino);

        return Trabajador::query()
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->when(
                $buscado !== '',
                fn (Collection $trabajadores) => $trabajadores->filter(
                    fn (Trabajador $trabajador) => str_contains(
                        self::normalizar("{$trabajador->rut} {$trabajador->nombres} {$trabajador->apellidos}"),
                        $buscado,
                    ),
                ),
            )
            ->take($limite)
            ->mapWithKeys(fn (Trabajador $trabajador) => [$trabajador->id => self::etiqueta($trabajador)])
            ->all();
    }

    /**
     * Callback de búsqueda para una columna propia del modelo (ej: nombres,
     * apellidos, cargo, rut). Se usa en ->searchable(query: Buscador::columna('nombres')).
     */
    public static function columna(string $columna): Closure
    {
        return fn (Builder $query, string $search): Builder => $query->whereRaw(
            self::sqlNormalizado($query->getModel()->qualifyColumn($columna)).' LIKE ?',
            ['%'.self::normalizar($search).'%'],
        );
    }

    /**
     * Callback de búsqueda para una columna de relación (ej: trabajador.nombres).
     * Busca el término normalizado en una o más columnas del modelo relacionado.
     *
     * Se usa en ->searchable(query: Buscador::relacion('trabajador', 'nombres', 'apellidos')).
     */
    public static function relacion(string $relacion, string ...$columnas): Closure
    {
        return fn (Builder $query, string $search): Builder => $query->whereHas(
            $relacion,
            function (Builder $relacionados) use ($columnas, $search): void {
                $termino = '%'.self::normalizar($search).'%';

                $relacionados->where(function (Builder $agrupados) use ($columnas, $termino): void {
                    foreach ($columnas as $columna) {
                        $agrupados->orWhereRaw(
                            self::sqlNormalizado($agrupados->getModel()->qualifyColumn($columna)).' LIKE ?',
                            [$termino],
                        );
                    }
                });
            },
        );
    }
}
