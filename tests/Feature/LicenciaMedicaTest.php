<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Services\SubsidioLicenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenciaMedicaTest extends TestCase
{
    use RefreshDatabase;

    private function crearTrabajadorConContrato(int $sueldoBase = 600000): Trabajador
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Juana',
            'apellidos' => 'Pérez',
            'cargo' => 'Cuidadora',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01',
        ]);

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => $sueldoBase,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2025-01-01',
        ]);

        ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 37000.50,
            'valor_utm' => 66000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
        ]);

        return $trabajador;
    }

    public function test_calcula_dias_de_reposo_automaticamente()
    {
        $trabajador = $this->crearTrabajadorConContrato();

        $licencia = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-14',
            'estado' => 'Pendiente',
        ]);

        // Del 5 al 14 de octubre son 10 días corridos (ambos inclusive).
        $this->assertEquals(10, $licencia->dias_reposo);
    }

    public function test_subsidio_licencia_comun_es_75_por_ciento_con_3_dias_de_carencia()
    {
        $trabajador = $this->crearTrabajadorConContrato(600000);

        $licencia = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-14',
            'estado' => 'Pendiente',
        ]);

        // Sueldo diario: 600000 / 30 = 20000
        // Tope diario: (60 * 37000.50) / 30 = 74001 -> no aplica
        // Subsidio diario: 20000 * 0.75 = 15000
        // Días pagados: 10 - 3 = 7
        // Subsidio total: 15000 * 7 = 105000
        $this->assertEquals(7, $licencia->dias_pagados);
        $this->assertEquals(75.0, $licencia->porcentaje_subsidio);
        $this->assertEquals(105000, $licencia->monto_subsidio);
    }

    public function test_subsidio_licencia_comun_aplica_tope_de_60_uf()
    {
        // Sueldo alto: 5.000.000 -> diario 166.667, sobre el tope diario de 74.001
        $trabajador = $this->crearTrabajadorConContrato(5000000);

        $licencia = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-14',
            'estado' => 'Pendiente',
        ]);

        // Tope diario: (60 * 37000.50) / 30 = 74001
        // Subsidio diario con tope: 74001 * 0.75 = 55500.75 -> 55501
        // Días pagados: 10 - 3 = 7 -> total 388507
        $this->assertEquals(7, $licencia->dias_pagados);
        $this->assertEquals(55501, $licencia->subsidio_diario);
        $this->assertEquals(55501 * 7, $licencia->monto_subsidio);
    }

    public function test_subsidio_maternal_y_accidente_pagan_100_por_ciento_sin_carencia()
    {
        $trabajador = $this->crearTrabajadorConContrato(600000);

        $maternal = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Maternal',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-10',
            'estado' => 'Pendiente',
        ]);

        // 10 días, sin carencia: 20000 * 10 = 200000
        $this->assertEquals(10, $maternal->dias_pagados);
        $this->assertEquals(100.0, $maternal->porcentaje_subsidio);
        $this->assertEquals(200000, $maternal->monto_subsidio);

        $laboral = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Accidente del Trabajo',
            'entidad_emisora' => 'Mutual de Seguridad',
            'fecha_inicio' => '2026-10-12',
            'fecha_fin' => '2026-10-16',
            'estado' => 'Pendiente',
        ]);

        // 5 días, 100%: 20000 * 5 = 100000
        $this->assertEquals(5, $laboral->dias_pagados);
        $this->assertEquals(100000, $laboral->monto_subsidio);
    }

    public function test_recalculo_al_editar_las_fechas()
    {
        $trabajador = $this->crearTrabajadorConContrato(600000);

        $licencia = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-09',
            'estado' => 'Pendiente',
        ]);

        // 5 días - 3 carencia = 2 pagados
        $this->assertEquals(2, $licencia->dias_pagados);
        $this->assertEquals(30000, $licencia->monto_subsidio);

        $licencia->update(['fecha_fin' => '2026-10-19']);

        // 15 días - 3 carencia = 12 pagados -> 180000
        $this->assertEquals(15, $licencia->fresh()->dias_reposo);
        $this->assertEquals(12, $licencia->fresh()->dias_pagados);
        $this->assertEquals(180000, $licencia->fresh()->monto_subsidio);
    }

    public function test_sin_contrato_no_calcula_subsidio_pero_guarda_la_licencia()
    {
        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Sin',
            'apellidos' => 'Contrato',
            'cargo' => 'Auxiliar',
            'afp' => 'Modelo',
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2025-01-01',
        ]);

        $licencia = LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-05',
            'estado' => 'Pendiente',
        ]);

        $this->assertEquals(5, $licencia->dias_reposo);
        $this->assertEquals(0, $licencia->monto_subsidio);
    }

    public function test_servicio_devuelve_ceros_sin_parametros_legales_del_periodo()
    {
        $trabajador = $this->crearTrabajadorConContrato();

        // Sin parámetros legales en la base de datos del período
        ParametroLegal::query()->delete();

        $licencia = new LicenciaMedica([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-14',
            'dias_reposo' => 10,
        ]);
        $licencia->setRelation('trabajador', $trabajador);

        $calculo = app(SubsidioLicenciaService::class)->calcular($licencia);

        $this->assertEquals(0, $calculo['subsidio_total']);
        $this->assertEquals(0, $calculo['dias_pagados']);
    }
}
