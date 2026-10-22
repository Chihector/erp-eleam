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
                            {--uf= : UF manual en pesos (punto decimal). Omite la consulta a mindicador.cl}
                            {--utm= : UTM manual en pesos. Omite la consulta a mindicador.cl}
                            {--verificar : Marca el período como verificado por una persona}
                            {--usuario= : ID o correo del usuario que verifica (obligatorio con --verificar)}';

    protected $description = 'Actualiza UF, UTM y sueldo mínimo de un período desde mindicador.cl (o con valores manuales) y la tabla de vigencia.';

    public function handle(ParametroLegalService $service): int
    {
        $mes = (int) ($this->option('mes') ?: now()->month);
        $anio = (int) ($this->option('anio') ?: now()->year);
        $sueldoMinimo = $this->option('sueldo-minimo');

        try {
            $uf = $this->decimalOpcional($this->option('uf'), 'UF');
            $utm = $this->enteroOpcional($this->option('utm'), 'UTM');

            $parametro = $service->actualizar(
                $mes,
                $anio,
                $sueldoMinimo !== null ? (int) $sueldoMinimo : null,
                $uf,
                $utm,
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

        if ($this->option('uf') !== null || $this->option('utm') !== null) {
            $this->warn('Usaste valores manuales de UF/UTM. Verifícalos contra el SII o el Banco Central antes de cerrar el período.');
        }

        $comandoVerificacion = 'php artisan eleam:parametros-actualizar --mes='.$mes.' --anio='.$anio;

        if ($this->option('uf') !== null) {
            $comandoVerificacion .= ' --uf='.$this->option('uf');
        }

        if ($this->option('utm') !== null) {
            $comandoVerificacion .= ' --utm='.$this->option('utm');
        }

        if ($sueldoMinimo !== null) {
            $comandoVerificacion .= ' --sueldo-minimo='.$sueldoMinimo;
        }

        $this->line('Para marcar el período como revisado: '.$comandoVerificacion.' --verificar --usuario=TU_CORREO');

        return self::SUCCESS;
    }

    private function decimalOpcional(mixed $valor, string $nombre): ?float
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $normalizado = $this->normalizarNumero((string) $valor);

        if (! is_numeric($normalizado)) {
            throw new RuntimeException("El valor de {$nombre} no es numérico: {$valor}");
        }

        return (float) $normalizado;
    }

    private function enteroOpcional(mixed $valor, string $nombre): ?int
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        $normalizado = $this->normalizarNumero((string) $valor);

        if (! is_numeric($normalizado)) {
            throw new RuntimeException("El valor de {$nombre} no es numérico: {$valor}");
        }

        return (int) round((float) $normalizado);
    }

    /**
     * Acepta formatos chilenos (41.057,20) y de máquina (41057.20).
     */
    private function normalizarNumero(string $valor): string
    {
        $valor = str_replace(' ', '', trim($valor));

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            return str_replace(',', '.', str_replace('.', '', $valor));
        }

        if (str_contains($valor, ',')) {
            return str_replace(',', '.', $valor);
        }

        if (substr_count($valor, '.') > 1) {
            return str_replace('.', '', $valor);
        }

        if (substr_count($valor, '.') === 1) {
            [$entero, $decimal] = explode('.', $valor);

            // 72.151 se interpreta como miles; 41057.20 como decimal.
            if (strlen($decimal) === 3) {
                return $entero.$decimal;
            }
        }

        return $valor;
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
