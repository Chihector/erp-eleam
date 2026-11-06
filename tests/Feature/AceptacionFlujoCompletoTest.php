<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\User;
use App\Models\Vacacion;
use App\Services\LibroRemuneracionesService;
use App\Services\RemuneracionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Prueba de aceptación de punta a punta: personal, turno, asistencia,
 * vacaciones, licencia, liquidación cerrada, PDF y libro DT.
 */
class AceptacionFlujoCompletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_completo_hasta_liquidacion_pdf_y_libro_dt(): void
    {
        foreach (['administrador', 'jefe de turno', 'contador', 'solo lectura'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }

        $admin = User::create([
            'email' => 'aceptacion@eleam.cl',
            'password' => 'password',
            'nombre' => 'Admin',
            'apellido' => 'Aceptación',
            'rut' => '11111111-1',
        ]);
        $admin->assignRole('administrador');

        $parametro = ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 41057.20,
            'valor_utm' => 72151,
            'sueldo_minimo' => 553553,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
            'porcentaje_sis' => 1.49,
            'origen' => 'aceptacion',
            'verificado_por_id' => $admin->id,
            'verificado_en' => now(),
        ]);

        $trabajador = Trabajador::create([
            'rut' => '12345678-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'Enfermera',
            'afp' => 'Modelo',
            'afp_tasa' => 10.58,
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2024-01-01',
        ]);

        $contrato = Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => 800000,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2024-01-01',
        ]);

        $turno = Turno::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-10-01 08:00:00',
            'fecha_fin' => '2026-10-01 20:00:00',
            'tipo_turno' => 'Día',
            'estado' => 'Completado',
        ]);

        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'turno_id' => $turno->id,
            'fecha_hora_entrada' => '2026-10-01 07:55:00',
            'fecha_hora_salida' => '2026-10-01 20:05:00',
        ]);

        Vacacion::create([
            'trabajador_id' => $trabajador->id,
            'fecha_inicio' => '2026-10-14',
            'fecha_fin' => '2026-10-16',
            'dias_habiles' => 3,
            'estado' => 'Aprobada',
        ]);

        LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'folio' => 'ACE PT-2026-10',
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-08',
            'estado' => 'Validada',
        ]);

        $servicio = app(RemuneracionService::class);
        $liquidacion = $servicio->calcularLiquidacion(
            $trabajador,
            10,
            2026,
            null,
            ['origen_dias' => RemuneracionService::ORIGEN_DIAS_LICENCIAS],
        );

        $this->assertSame(26, $liquidacion->dias_trabajados);
        $this->assertSame($contrato->id, $liquidacion->contrato_id);
        $this->assertSame($parametro->id, $liquidacion->parametro_legal_id);
        $this->assertSame(553553, $liquidacion->sueldo_minimo_usado);

        $this->actingAs($admin);
        $servicio->cerrar($liquidacion, $admin);

        $this->assertTrue($liquidacion->fresh()->cerrada);

        $respuesta = $this->get("/admin/liquidaciones/{$liquidacion->id}/pdf?download=1");

        $respuesta->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $respuesta->headers->get('content-type'),
        );
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());

        $csv = app(LibroRemuneracionesService::class)->generarCsv(10, 2026);

        $this->assertTrue(str_starts_with($csv, "\xEF\xBB\xBF"));
        $this->assertStringContainsString('12345678-9', $csv);
        $this->assertStringContainsString('Ana', $csv);
        $this->assertStringContainsString('Pérez', $csv);
        $this->assertSame(1, app(LibroRemuneracionesService::class)->cantidadDelPeriodo(10, 2026));
    }
}
