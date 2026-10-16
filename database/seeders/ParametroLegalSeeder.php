<?php

namespace Database\Seeders;

use App\Models\ParametroLegal;
use Illuminate\Database\Seeder;

class ParametroLegalSeeder extends Seeder
{
    public function run(): void
    {
        $anios = [2025, 2026];

        foreach ($anios as $anio) {
            for ($mes = 1; $mes <= 12; $mes++) {
                ParametroLegal::firstOrCreate(
                    [
                        'mes' => $mes,
                        'anio' => $anio,
                    ],
                    [
                        'valor_uf' => 38000.00,
                        'valor_utm' => 67000.00,
                        'sueldo_minimo' => 500000,
                        'tope_imponible_afp' => 84.3,
                        'tope_imponible_ips' => 60.0,
                        'tope_imponible_cesantia' => 126.6,
                        'porcentaje_sis' => 1.49,
                    ]
                );
            }
        }
    }
}
