<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\Liquidacion;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\RemuneracionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cobertura de los cierres P0 de remuneraciones:
 *  - Impuesto único de segunda categoría.
 *  - Descuentos adicionales (anticipo, sindicato) y haberes no imponibles.
 *  - Días trabajados desde licencias o asistencias.
 *  - Cierre y reapertura con permisos y trazabilidad.
 */
class LiquidacionP0Test extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    private User $contador;

    private User $soloLectura;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['administrador', 'jefe de turno', 'contador', 'solo lectura'] as $rol) {
            Role::firstOrCreate(['name' => $rol]);
        }

        $this->administrador = $this->crearUsuario('administrador');
        $this->contador = $this->crearUsuario('contador');
        $this->soloLectura = $this->crearUsuario('solo lectura');
    }

    private function crearUsuario(string $rol): User
    {
        static $numero = 0;
        $numero++;

        $usuario = User::create([
            'email' => "p0-{$numero}@eleam.cl",
            'password' => 'password',
            'nombre' => 'Usuario',
            'apellido' => "P0 {$numero}",
            'rut' => sprintf('20%07d-%d', $numero, $numero % 10),
        ]);

        $usuario->assignRole($rol);

        return $usuario;
    }

    private function crearParametro(bool $verificado = false): ParametroLegal
    {
        return ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 41057.20,
            'valor_utm' => 72151,
            'sueldo_minimo' => 553553,
            'tope_imponible_afp' => 84.3,
            'tope_imponible_ips' => 60.0,
            'tope_imponible_cesantia' => 126.6,
            'porcentaje_sis' => 1.49,
            'origen' => 'prueba',
            'verificado_por_id' => $verificado ? $this->administrador->id : null,
            'verificado_en' => $verificado ? now() : null,
        ]);
    }

    private function crearTrabajador(int $sueldoBase = 500000, array $cambios = []): Trabajador
    {
        $trabajador = Trabajador::create(array_merge([
            'rut' => '12345678-9',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'cargo' => 'Enfermera',
            'afp' => 'Modelo',
            'afp_tasa' => 10.58,
            'sistema_salud' => 'Fonasa',
            'fecha_ingreso' => '2024-01-01',
        ], $cambios));

        Contrato::create([
            'trabajador_id' => $trabajador->id,
            'sueldo_base' => $sueldoBase,
            'tipo_contrato' => 'Indefinido',
            'horas_semanales' => 45,
            'fecha_inicio' => '2024-01-01',
        ]);

        return $trabajador;
    }

    public function test_calcula_impuesto_unico_con_la_tabla_sii_vigente(): void
    {
        $servicio = app(RemuneracionService::class);
        $utm = 72151;

        // 13,5 UTM exactas: exento.
        $this->assertSame(0, $servicio->calcularImpuestoSegundaCategoria(974_038, $utm));

        // Ejemplo SII: 4 % con rebaja de 0,54 UTM.
        $this->assertSame(21038, $servicio->calcularImpuestoSegundaCategoria(1_500_000, $utm));

        // 8 % con rebaja de 1,74 UTM.
        $this->assertSame(114457, $servicio->calcularImpuestoSegundaCategoria(3_000_000, $utm));

        // 35 % con rebaja de 23,32 UTM.
        $this->assertSame(1817439, $servicio->calcularImpuestoSegundaCategoria(10_000_000, $utm));

        // Sin renta o sin UTM no hay impuesto.
        $this->assertSame(0, $servicio->calcularImpuestoSegundaCategoria(0, $utm));
        $this->assertSame(0, $servicio->calcularImpuestoSegundaCategoria(1_000_000, 0));
    }

    public function test_liquidacion_incluye_iusc_y_descuentos_adicionales(): void
    {
        $this->crearParametro();
        $trabajador = $this->crearTrabajador(1_500_000);

        $liquidacion = app(RemuneracionService::class)->calcularLiquidacion(
            $trabajador,
            10,
            2026,
            30,
            [
                'anticipo' => 100_000,
                'descuento_sindicato' => 5_000,
                'colacion' => 50_000,
                'cargas_familiares' => 10_000,
            ],
        );

        // Imponible: sueldo + gratificación con tope legal.
        $gratificacion = (int) round(min(1_500_000 * 0.25, 4.75 * 553_553 / 12));
        $totalImponible = 1_500_000 + $gratificacion;
        $this->assertSame($totalImponible, $liquidacion->total_imponible);

        $afp = (int) round($totalImponible * 0.1058);
        $salud = (int) round($totalImponible * 0.07);
        $cesantia = (int) round($totalImponible * 0.006);
        $rentaLiquida = $totalImponible - $afp - $salud - $cesantia;
        $impuesto = (int) round($rentaLiquida * 0.04 - (0.54 * 72_151));

        $this->assertSame($rentaLiquida, $liquidacion->renta_liquida_imponible);
        $this->assertSame($impuesto, $liquidacion->impuesto_segunda_categoria);
        $this->assertSame($afp + $salud + $cesantia + $impuesto, $liquidacion->total_descuentos_legales);

        $this->assertSame(60_000, $liquidacion->total_no_imponible);
        $this->assertSame($totalImponible + 60_000, $liquidacion->total_haberes);
        $this->assertSame(105_000, $liquidacion->otros_descuentos);
        $this->assertSame(100_000, $liquidacion->descuento_anticipo);
        $this->assertSame(5_000, $liquidacion->descuento_sindicato);
        $this->assertSame($totalImponible + 60_000 - ($afp + $salud + $cesantia + $impuesto) - 105_000, $liquidacion->sueldo_liquido);

        // Se guardan los parámetros exactos usados para reproducir el cálculo.
        $this->assertSame(41057.20, (float) $liquidacion->valor_uf_usado);
        $this->assertSame(72151, $liquidacion->valor_utm_usado);
        $this->assertNotNull($liquidacion->parametro_legal_id);
    }

    public function test_calcula_dias_desde_licencias_no_rechazadas(): void
    {
        $this->crearParametro();
        $trabajador = $this->crearTrabajador();

        LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-10-09',
            'estado' => 'Validada',
        ]);

        LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-20',
            'fecha_fin' => '2026-10-21',
            'estado' => 'Rechazada',
        ]);

        $servicio = app(RemuneracionService::class);

        $this->assertSame(25, $servicio->diasTrabajadosSugeridos($trabajador, 10, 2026, 'licencias'));

        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, null, [
            'origen_dias' => 'licencias',
        ]);

        $this->assertSame(25, $liquidacion->dias_trabajados);
        $this->assertSame('licencias', $liquidacion->origen_dias);
        $this->assertSame((int) round(500000 * 25 / 30), $liquidacion->sueldo_base);
    }

    public function test_calcula_dias_desde_asistencias_y_excluye_dias_con_licencia(): void
    {
        $this->crearParametro();
        $trabajador = $this->crearTrabajador();

        foreach (['2026-10-01', '2026-10-02', '2026-10-05', '2026-10-06', '2026-10-07'] as $dia) {
            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha_hora_entrada' => "{$dia} 08:00:00",
                'fecha_hora_salida' => "{$dia} 20:00:00",
            ]);
        }

        LicenciaMedica::create([
            'trabajador_id' => $trabajador->id,
            'tipo' => 'Común',
            'entidad_emisora' => 'Fonasa',
            'fecha_inicio' => '2026-10-06',
            'fecha_fin' => '2026-10-06',
            'estado' => 'Validada',
        ]);

        $servicio = app(RemuneracionService::class);

        // 5 marcas, una coincide con licencia: quedan 4 días efectivos.
        $this->assertSame(4, $servicio->diasTrabajadosSugeridos($trabajador, 10, 2026, 'asistencia'));

        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, null, [
            'origen_dias' => 'asistencia',
        ]);

        $this->assertSame(4, $liquidacion->dias_trabajados);
        $this->assertSame('asistencia', $liquidacion->origen_dias);
    }

    public function test_modo_asistencia_falla_si_no_hay_marcas(): void
    {
        $this->crearParametro();
        $trabajador = $this->crearTrabajador();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No hay asistencias registradas');

        app(RemuneracionService::class)->diasTrabajadosSugeridos($trabajador, 10, 2026, 'asistencia');
    }

    public function test_cierre_exige_parametros_verificados(): void
    {
        $this->crearParametro(false);
        $trabajador = $this->crearTrabajador();
        $liquidacion = app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('aún no están verificados');

        app(RemuneracionService::class)->cerrar($liquidacion, $this->administrador);
    }

    public function test_cierre_bloquea_recalculo_hasta_reabrir_con_motivo(): void
    {
        $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();
        $servicio = app(RemuneracionService::class);
        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->actingAs($this->administrador);
        config(['audit.console' => true]);

        $servicio->cerrar($liquidacion, $this->administrador);
        $liquidacion->refresh();

        $this->assertTrue($liquidacion->cerrada);
        $this->assertSame($this->administrador->id, $liquidacion->cerrada_por_id);
        $this->assertNotNull($liquidacion->cerrada_en);
        $this->assertGreaterThan(0, $liquidacion->audits()->count());

        try {
            $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);
            $this->fail('El recálculo de una liquidación cerrada debió fallar.');
        } catch (Exception $e) {
            $this->assertStringContainsString('está cerrada', $e->getMessage());
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('motivo');

        $servicio->reabrir($liquidacion, $this->administrador, '   ');
    }

    public function test_reabrir_registra_motivo_usuario_y_fecha(): void
    {
        $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();
        $servicio = app(RemuneracionService::class);
        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->actingAs($this->administrador);

        $servicio->cerrar($liquidacion, $this->administrador);
        $servicio->reabrir($liquidacion, $this->administrador, 'Corrección de días trabajados informados por RRHH.');
        $liquidacion->refresh();

        $this->assertFalse($liquidacion->cerrada);
        $this->assertSame($this->administrador->id, $liquidacion->reabierta_por_id);
        $this->assertNotNull($liquidacion->reabierta_en);
        $this->assertSame('Corrección de días trabajados informados por RRHH.', $liquidacion->motivo_reapertura);

        // Ya reabierta, el cálculo puede volver a ejecutarse.
        $recalculada = $servicio->calcularLiquidacion($trabajador, 10, 2026, 20);
        $this->assertSame(20, $recalculada->dias_trabajados);
        $this->assertFalse($recalculada->cerrada);
    }

    public function test_cierre_rechaza_si_los_parametros_cambiaron_despues_del_calculo(): void
    {
        $parametro = $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();
        $servicio = app(RemuneracionService::class);
        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);

        // Un cambio directo de parámetros no borra la verificación, pero el
        // cierre debe detectar que ya no coinciden con el cálculo original.
        $parametro->update(['valor_utm' => 73000]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('cambiaron');

        $servicio->cerrar($liquidacion, $this->administrador);
    }

    public function test_guarda_una_copia_completa_de_los_parametros_usados(): void
    {
        $parametro = $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();

        $liquidacion = app(RemuneracionService::class)
            ->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->assertSame($parametro->id, $liquidacion->parametro_legal_id);
        $this->assertSame(41057.20, (float) $liquidacion->valor_uf_usado);
        $this->assertSame(72151, $liquidacion->valor_utm_usado);
        $this->assertSame(553553, $liquidacion->sueldo_minimo_usado);
        $this->assertSame(84.3, (float) $liquidacion->tope_afp_usado);
        $this->assertSame(60.0, (float) $liquidacion->tope_ips_usado);
        $this->assertSame(126.6, (float) $liquidacion->tope_cesantia_usado);
        $this->assertSame(1.49, (float) $liquidacion->sis_porcentaje);
    }

    public function test_cierre_detecta_cambios_en_cada_parametro_legal(): void
    {
        $parametro = $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();
        $servicio = app(RemuneracionService::class);
        $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);

        $cambios = [
            'UF' => ['campo' => 'valor_uf', 'valor' => 42000.00],
            'UTM' => ['campo' => 'valor_utm', 'valor' => 73000],
            'sueldo mínimo' => ['campo' => 'sueldo_minimo', 'valor' => 560000],
            'tope AFP' => ['campo' => 'tope_imponible_afp', 'valor' => 85.0],
            'tope salud' => ['campo' => 'tope_imponible_ips', 'valor' => 61.0],
            'tope cesantía' => ['campo' => 'tope_imponible_cesantia', 'valor' => 127.0],
            'SIS' => ['campo' => 'porcentaje_sis', 'valor' => 1.5],
        ];

        foreach ($cambios as $etiqueta => $cambio) {
            $original = $parametro->{$cambio['campo']};
            $parametro->update([$cambio['campo'] => $cambio['valor']]);

            try {
                $servicio->cerrar($liquidacion, $this->administrador);
                $this->fail("El cierre debió fallar al cambiar {$etiqueta}.");
            } catch (Exception $e) {
                $this->assertStringContainsString('cambiaron', $e->getMessage());
                $this->assertStringContainsString($etiqueta, $e->getMessage());
            }

            $parametro->update([$cambio['campo'] => $original]);
            $liquidacion = $servicio->calcularLiquidacion($trabajador, 10, 2026, 30);
        }

        // Con todos los parámetros restaurados, el cierre debe funcionar.
        $servicio->cerrar($liquidacion, $this->administrador);
        $this->assertTrue($liquidacion->fresh()->cerrada);
    }

    public function test_permisos_de_cierre_y_reapertura(): void
    {
        $this->crearParametro(true);
        $trabajador = $this->crearTrabajador();
        $liquidacion = app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026, 30);

        $this->assertTrue($this->contador->can('cerrar', $liquidacion));
        $this->assertFalse($this->soloLectura->can('cerrar', $liquidacion));
        $this->assertFalse($this->contador->can('reabrir', $liquidacion));

        app(RemuneracionService::class)->cerrar($liquidacion, $this->contador);
        $liquidacion->refresh();

        $this->assertFalse($this->contador->can('update', $liquidacion));
        $this->assertFalse($this->contador->can('delete', $liquidacion));
        $this->assertFalse($this->administrador->can('update', $liquidacion));
        $this->assertTrue($this->administrador->can('reabrir', $liquidacion));
        $this->assertFalse($this->contador->can('reabrir', $liquidacion));
    }

    public function test_eliminar_liquidacion_la_archiva_y_permite_restaurarla(): void
    {
        $this->crearParametro();
        $trabajador = $this->crearTrabajador();
        $liquidacion = app(RemuneracionService::class)->calcularLiquidacion($trabajador, 10, 2026, 30);

        // Solo un administrador puede restaurar y nunca se borra en duro.
        $this->assertTrue($this->administrador->can('restore', $liquidacion));
        $this->assertFalse($this->contador->can('restore', $liquidacion));
        $this->assertFalse($this->administrador->can('forceDelete', $liquidacion));

        $liquidacion->delete();

        $this->assertSoftDeleted('liquidacions', ['id' => $liquidacion->id]);
        $this->assertSame(0, Liquidacion::count());
        $this->assertSame(1, Liquidacion::withTrashed()->count());

        $liquidacion->restore();

        $this->assertSame(1, Liquidacion::count());
        $this->assertFalse($liquidacion->fresh()->trashed());
    }
}
