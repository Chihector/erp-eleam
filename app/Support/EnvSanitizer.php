<?php

namespace App\Support;

/**
 * Oculta credenciales y secretos de una copia de .env antes de respaldarla.
 */
class EnvSanitizer
{
    /**
     * Reemplaza el valor de las variables sensibles por una marca fija.
     */
    public static function sanitizar(string $contenido): string
    {
        $lineas = preg_split('/\r\n|\r|\n/', $contenido) ?: [];
        $palabrasClave = ['KEY', 'SECRET', 'PASSWORD', 'TOKEN', 'PRIVATE'];

        foreach ($lineas as $i => $linea) {
            if (str_starts_with(trim($linea), '#') || ! str_contains($linea, '=')) {
                continue;
            }

            [$clave] = explode('=', $linea, 2);
            $clave = strtoupper(trim($clave));

            foreach ($palabrasClave as $palabra) {
                if (str_contains($clave, $palabra)) {
                    $lineas[$i] = "{$clave}=***REDACTADO***";
                    break;
                }
            }
        }

        return implode("\n", $lineas);
    }
}
