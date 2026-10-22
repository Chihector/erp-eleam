<?php

namespace Database\Seeders;

use App\Models\ParametroLegal;
use App\Services\ParametroLegalService;
use Illuminate\Database\Seeder;

class ParametroLegalSeeder extends Seeder
{
    public function run(): void
    {
        $mes = now()->month;
        $anio = now()->year;

        ParametroLegal::updateOrCreate(
            [
                'mes' => $mes,
                'anio' => $anio,
            ],
            [
                // Valores de referencia al 2026-10-22. El comando
                // eleam:parametros-actualizar los reemplaza desde mindicador.cl.
                'valor_uf' => 41199.85,
                'valor_utm' => 72151,
                'sueldo_minimo' => app(ParametroLegalService::class)->sueldoMinimoVigente($mes, $anio),
                'tope_imponible_afp' => config('legal.topes.afp'),
                'tope_imponible_ips' => config('legal.topes.ips'),
                'tope_imponible_cesantia' => config('legal.topes.cesantia'),
                'porcentaje_sis' => config('legal.sis'),
                'origen' => 'semilla (valores de referencia sin verificar)',
                'fuente_url' => 'https://mindicador.cl/api',
                'actualizado_en' => now(),
            ]
        );

        $this->command?->warn(
            'Parámetros legales de '.str_pad((string) $mes, 2, '0', STR_PAD_LEFT)."/{$anio} cargados como referencia SIN VERIFICAR. "
            .'Ejecuta php artisan eleam:parametros-actualizar y revisa los valores antes de pagar remuneraciones.',
        );
    }
}
