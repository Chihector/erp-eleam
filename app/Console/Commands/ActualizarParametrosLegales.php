<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ParametroLegalService;
use Illuminate\Console\Command;
use Throwable;

class ActualizarParametrosLegales extends Command
{
    protected $signature = 'eleam:parametros-actualizar
                            {--mes= : Mes del período (1-12). Por defecto, el mes actual}
                            {--anio= : Año del período. Por defecto, el año actual}
                            {--sueldo-minimo= : Sueldo mínimo en pesos. Si se omite, usa la tabla de vigencia configurada}
                            {--verificar : Marca el período como verificado por una persona}
                            {--usuario= : ID o correo del usuario que verifica (obligatorio con --verificar)}';

    protected $description = 'Actualiza UF, UTM y sueldo mínimo de un período desde mindicador.cl y la tabla de vigencia.';

    public function handle(ParametroLegalService $service): int
    {
        $mes = (int) ($this->option('mes') ?: now()->month);
        $anio = (int) ($this->option('anio') ?: now()->year);
        $sueldoMinimo = $this->option('sueldo-minimo');

        try {
            $parametro = $service->actualizar(
                $mes,
                $anio,
                $sueldoMinimo !== null ? (int) $sueldoMinimo : null,
            );

            $verificadoPor = null;

            if ($this->option('verificar')) {
                $usuario = $this->resolverUsuario($this->option('usuario'));

                if (! $usuario) {
                    return self::FAILURE;
                }

                $service->marcarVerificado($parametro, $usuario->id);
                $parametro->refresh();
                $verificadoPor = $usuario->email;
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Período', str_pad((string) $mes, 2, '0', STR_PAD_LEFT).'/'.$anio],
                ['UF', '$'.number_format($parametro->valor_uf, 2, ',', '.')],
                ['UTM', '$'.number_format($parametro->valor_utm, 0, ',', '.')],
                ['Sueldo mínimo', '$'.number_format($parametro->sueldo_minimo, 0, ',', '.')],
                ['Tope AFP', $parametro->tope_imponible_afp.' UF'],
                ['Tope cesantía', $parametro->tope_imponible_cesantia.' UF'],
                ['SIS', $parametro->porcentaje_sis.'% (aporte empleador)'],
                ['Origen', $parametro->origen ?? 'manual'],
                ['Verificado', $verificadoPor ?? 'NO — requiere revisión humana'],
            ],
        );

        $this->newLine();
        $this->warn('La UF y la UTM son datos de referencia. Confirma los topes, el SIS y el sueldo mínimo con la normativa vigente antes de pagar remuneraciones.');
        $this->line('Para marcar el período como revisado: php artisan eleam:parametros-actualizar --mes='.$mes.' --anio='.$anio.' --verificar --usuario=TU_CORREO');

        return self::SUCCESS;
    }

    private function resolverUsuario(?string $identificador): ?User
    {
        if ($identificador === null || trim($identificador) === '') {
            $this->error('Debes indicar --usuario=correo@ejemplo.cl o --usuario=ID al usar --verificar.');

            return null;
        }

        $usuario = User::query()
            ->where('email', $identificador)
            ->when(ctype_digit($identificador), fn ($query) => $query->orWhere('id', (int) $identificador))
            ->first();

        if (! $usuario) {
            $this->error("No existe un usuario con correo o ID {$identificador}.");

            return null;
        }

        return $usuario;
    }
}
