<?php

namespace App\Services;

use App\Models\ParametroLegal;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Obtiene y registra los parámetros legales de un período.
 *
 * La UF y la UTM se consultan desde mindicador.cl. El sueldo mínimo se toma
 * de la tabla de vigencia configurada (o se informa manualmente), porque no
 * se publica en esa API. Los topes previsionales y el SIS se conservan del
 * período anterior o de la configuración: no se inventan valores.
 *
 * Ningún valor queda "verificado" automáticamente. La verificación es un acto
 * humano y queda registrada con usuario y fecha.
 */
class ParametroLegalService
{
    public function actualizar(int $mes, int $anio, ?int $sueldoMinimo = null): ParametroLegal
    {
        $this->validarPeriodo($mes, $anio);

        $baseUrl = rtrim((string) config('legal.parametros_url'), '/');

        $utm = $this->obtenerUtm($baseUrl, $mes, $anio);
        $uf = $this->obtenerUf($baseUrl, $mes, $anio);
        $sueldo = $sueldoMinimo ?? $this->sueldoMinimoVigente($mes, $anio);

        if ($sueldo < 1) {
            throw new RuntimeException('El sueldo mínimo debe ser un monto positivo en pesos.');
        }

        $existente = ParametroLegal::query()
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        $topes = config('legal.topes');

        $cambio = ! $existente
            || abs((float) $existente->valor_uf - $uf) >= 0.01
            || (int) $existente->valor_utm !== $utm
            || (int) $existente->sueldo_minimo !== $sueldo;

        return ParametroLegal::updateOrCreate(
            ['mes' => $mes, 'anio' => $anio],
            [
                'valor_uf' => $uf,
                'valor_utm' => $utm,
                'sueldo_minimo' => $sueldo,
                'tope_imponible_afp' => $existente?->tope_imponible_afp ?? $topes['afp'],
                'tope_imponible_ips' => $existente?->tope_imponible_ips ?? $topes['ips'],
                'tope_imponible_cesantia' => $existente?->tope_imponible_cesantia ?? $topes['cesantia'],
                'porcentaje_sis' => $existente?->porcentaje_sis ?? config('legal.sis'),
                'origen' => 'mindicador.cl',
                'fuente_url' => $baseUrl,
                'actualizado_en' => now(),
                // Un cambio de valores exige una nueva revisión humana.
                'verificado_por_id' => $cambio ? null : $existente?->verificado_por_id,
                'verificado_en' => $cambio ? null : $existente?->verificado_en,
            ],
        );
    }

    public function marcarVerificado(ParametroLegal $parametro, int $usuarioId): ParametroLegal
    {
        $parametro->update([
            'verificado_por_id' => $usuarioId,
            'verificado_en' => now(),
        ]);

        return $parametro->refresh();
    }

    public function sueldoMinimoVigente(int $mes, int $anio): int
    {
        $limite = CarbonImmutable::create($anio, $mes, 1)->endOfMonth();

        $valor = collect(config('legal.sueldo_minimo', []))
            ->filter(fn (int $monto, string $desde): bool => CarbonImmutable::parse($desde)->lessThanOrEqualTo($limite))
            ->sortKeysDesc()
            ->first();

        if ($valor === null) {
            throw new RuntimeException(
                "No hay sueldo mínimo configurado para {$mes}/{$anio}. Indícalo con --sueldo-minimo.",
            );
        }

        return (int) $valor;
    }

    private function obtenerUtm(string $baseUrl, int $mes, int $anio): int
    {
        try {
            $respuesta = $this->cliente()->get("{$baseUrl}/utm/{$anio}")->throw();
        } catch (Throwable $e) {
            throw new RuntimeException(
                "No se pudo consultar la UTM {$mes}/{$anio} en mindicador.cl. Revisa la conexión o ingresa los valores manualmente.",
                0,
                $e,
            );
        }

        $serie = $respuesta->json('serie');

        if (! is_array($serie)) {
            throw new RuntimeException('La respuesta de UTM no tiene el formato esperado.');
        }

        foreach ($serie as $registro) {
            if (! isset($registro['fecha'], $registro['valor'])) {
                continue;
            }

            $fecha = CarbonImmutable::parse($registro['fecha']);

            if ($fecha->month === $mes && $fecha->year === $anio) {
                return (int) round((float) $registro['valor']);
            }
        }

        throw new RuntimeException(
            "mindicador.cl no publica la UTM de {$mes}/{$anio}. Si el mes aún no comienza, usa los parámetros del período anterior o ingrésalos manualmente.",
        );
    }

    private function obtenerUf(string $baseUrl, int $mes, int $anio): float
    {
        // Criterio de remuneraciones: UF del último día del mes anterior.
        $fechas = [
            CarbonImmutable::create($anio, $mes, 1)->subDay(),
            CarbonImmutable::create($anio, $mes, 1),
        ];

        $ultimoError = null;

        foreach ($fechas as $fecha) {
            try {
                $respuesta = $this->cliente()
                    ->get("{$baseUrl}/uf/{$fecha->format('d-m-Y')}")
                    ->throw();

                $valor = $respuesta->json('serie.0.valor');

                if (is_numeric($valor)) {
                    return (float) $valor;
                }
            } catch (Throwable $e) {
                $ultimoError = $e;
            }
        }

        throw new RuntimeException(
            "No se pudo consultar la UF de {$mes}/{$anio} en mindicador.cl. Revisa la conexión o ingresa los valores manualmente.",
            0,
            $ultimoError,
        );
    }

    private function cliente(): PendingRequest
    {
        return Http::acceptJson()->timeout(10)->retry(2, 300);
    }

    private function validarPeriodo(int $mes, int $anio): void
    {
        if ($mes < 1 || $mes > 12) {
            throw new RuntimeException('El mes debe estar entre 1 y 12.');
        }

        if ($anio < 2000 || $anio > 2100) {
            throw new RuntimeException('El año indicado no es válido.');
        }
    }
}
