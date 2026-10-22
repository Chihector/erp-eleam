<?php

namespace Tests\Feature;

use App\Models\ParametroLegal;
use App\Models\User;
use App\Services\ParametroLegalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ParametroLegalServiceTest extends TestCase
{
    use RefreshDatabase;

    private float $ufFake = 41000.50;

    private int $utmFake = 72151;

    private bool $fakeRegistrado = false;

    private function fakeMindicador(float $uf = 41000.50, int $utm = 72151): void
    {
        $this->ufFake = $uf;
        $this->utmFake = $utm;

        if ($this->fakeRegistrado) {
            return;
        }

        // Un solo callback permite cambiar los valores durante el mismo test;
        // Http::fake() acumula stubs y el primero que coincide gana.
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/utm/')) {
                return Http::response([
                    'serie' => [
                        ['fecha' => '2026-10-01T03:00:00.000Z', 'valor' => $this->utmFake],
                        ['fecha' => '2026-11-01T03:00:00.000Z', 'valor' => 72440],
                    ],
                ]);
            }

            return Http::response([
                'serie' => [
                    ['fecha' => '2026-09-30T03:00:00.000Z', 'valor' => $this->ufFake],
                ],
            ]);
        });

        $this->fakeRegistrado = true;
    }

    public function test_actualiza_uf_utm_y_sueldo_minimo_del_periodo(): void
    {
        $this->fakeMindicador();

        $parametro = app(ParametroLegalService::class)->actualizar(10, 2026, 553553);

        $this->assertSame(41000.50, $parametro->valor_uf);
        $this->assertSame(72151, $parametro->valor_utm);
        $this->assertSame(553553, $parametro->sueldo_minimo);
        $this->assertSame(84.3, $parametro->tope_imponible_afp);
        $this->assertSame('mindicador.cl', $parametro->origen);
        $this->assertSame('https://mindicador.cl/api', $parametro->fuente_url);
        $this->assertNotNull($parametro->actualizado_en);
        $this->assertNull($parametro->verificado_en);
        $this->assertFalse($parametro->estaVerificado());
    }

    public function test_usa_el_sueldo_minimo_configurado_si_no_se_indica(): void
    {
        $this->fakeMindicador();

        $service = app(ParametroLegalService::class);

        $this->assertSame(553553, $service->sueldoMinimoVigente(5, 2026));
        $this->assertSame(539000, $service->sueldoMinimoVigente(4, 2026));
        $this->assertSame(553553, $service->actualizar(10, 2026)->sueldo_minimo);
    }

    public function test_la_verificacion_requiere_usuario_y_se_conserva_si_no_cambian_los_valores(): void
    {
        $this->fakeMindicador();

        $usuario = User::create([
            'email' => 'contador@eleam.cl',
            'password' => 'password',
            'nombre' => 'Revisora',
            'apellido' => 'Contable',
            'rut' => '12345678-9',
        ]);

        $service = app(ParametroLegalService::class);
        $parametro = $service->actualizar(10, 2026, 553553);
        $service->marcarVerificado($parametro, $usuario->id);

        $this->assertTrue($parametro->refresh()->estaVerificado());
        $this->assertSame($usuario->id, $parametro->verificado_por_id);

        // Mismos valores: la revisión se conserva.
        $service->actualizar(10, 2026, 553553);
        $this->assertTrue($parametro->refresh()->estaVerificado());

        // Cambia la UF: exige una nueva revisión humana.
        $this->fakeMindicador(42000.00);
        $service->actualizar(10, 2026, 553553);
        $this->assertFalse($parametro->refresh()->estaVerificado());
        $this->assertNull($parametro->verificado_por_id);
    }

    public function test_falla_si_la_utm_del_periodo_no_esta_publicada(): void
    {
        Http::fake([
            'mindicador.cl/api/utm/2027' => Http::response(['serie' => []]),
            'mindicador.cl/api/uf/*' => Http::response(['serie' => [['valor' => 41000]]]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no publica la UTM');

        app(ParametroLegalService::class)->actualizar(1, 2027, 553553);
    }

    public function test_conserva_los_topes_del_periodo_existente(): void
    {
        ParametroLegal::create([
            'mes' => 10,
            'anio' => 2026,
            'valor_uf' => 38000,
            'valor_utm' => 67000,
            'sueldo_minimo' => 500000,
            'tope_imponible_afp' => 80.0,
            'tope_imponible_ips' => 55.0,
            'tope_imponible_cesantia' => 120.0,
            'porcentaje_sis' => 1.2,
        ]);

        $this->fakeMindicador();

        $parametro = app(ParametroLegalService::class)->actualizar(10, 2026, 553553);

        $this->assertSame(80.0, $parametro->tope_imponible_afp);
        $this->assertSame(55.0, $parametro->tope_imponible_ips);
        $this->assertSame(120.0, $parametro->tope_imponible_cesantia);
        $this->assertSame(1.2, $parametro->porcentaje_sis);
        $this->assertSame(1, ParametroLegal::count());
    }
}
