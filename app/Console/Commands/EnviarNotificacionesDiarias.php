<?php

namespace App\Console\Commands;

use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\Trabajador;
use App\Models\User;
use App\Notifications\ContratoPorVencerNotification;
use App\Notifications\LicenciaMedicaAlertaNotification;
use App\Notifications\VacacionesPendientesNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

class EnviarNotificacionesDiarias extends Command
{
    protected $signature = 'notificaciones:diarias
                            {--dry-run : Ejecutar sin enviar notificaciones reales}
                            {--solo= : Solo un tipo: contratos, vacaciones, licencias}';

    protected $description = 'Envía notificaciones diarias: contratos por vencer, vacaciones pendientes, licencias médicas';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $solo = $this->option('solo');

        $this->info('Iniciando envío de notificaciones diarias...'.($dryRun ? ' [MODO PRUEBA]' : ''));

        $enviadas = 0;

        if (! $solo || $solo === 'contratos') {
            $enviadas += $this->verificarContratos($dryRun);
        }

        if (! $solo || $solo === 'vacaciones') {
            $enviadas += $this->verificarVacaciones($dryRun);
        }

        if (! $solo || $solo === 'licencias') {
            $enviadas += $this->verificarLicencias($dryRun);
        }

        $this->info("Notificaciones procesadas: {$enviadas}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se enviaron notificaciones reales.');
        }

        return Command::SUCCESS;
    }

    private function verificarContratos(bool $dryRun): int
    {
        $hoy = Carbon::today();
        $en30Dias = $hoy->copy()->addDays(30);

        $contratos = Contrato::with('trabajador')
            ->whereNotNull('fecha_termino')
            ->where('fecha_termino', '<=', $en30Dias->toDateString())
            ->where('fecha_termino', '>=', $hoy->toDateString())
            ->get();

        $count = 0;

        foreach ($contratos as $contrato) {
            $diasRestantes = $hoy->diffInDays($contrato->fecha_termino, false);

            // Solo notificar en hitos específicos: 30, 15, 7, 3, 1 días
            if (! in_array($diasRestantes, [30, 15, 7, 3, 1])) {
                continue;
            }

            $admins = $this->obtenerAdmins();

            if ($admins->isEmpty()) {
                $this->warn("No hay administradores para notificar contrato {$contrato->id}");

                continue;
            }

            if (! $dryRun) {
                Notification::send($admins, new ContratoPorVencerNotification($contrato, $diasRestantes));
            }

            $this->line("  📄 Contrato {$contrato->id} ({$contrato->trabajador->nombres} {$contrato->trabajador->apellidos}) - {$diasRestantes} días");
            $count++;
        }

        return $count;
    }

    private function verificarVacaciones(bool $dryRun): int
    {
        // Trabajadores con más de 10 días pendientes (saldo calculado, no columna)
        $trabajadores = Trabajador::conVacacionesPendientesMayorA(10);

        $count = 0;

        foreach ($trabajadores as $trabajador) {
            $admins = $this->obtenerAdmins();

            if ($admins->isEmpty()) {
                continue;
            }

            // Solo notificar una vez por semana para evitar spam
            $ultimaNotif = DatabaseNotification::query()
                ->where('type', VacacionesPendientesNotification::class)
                ->where('data->trabajador_id', $trabajador->id)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->exists();

            if ($ultimaNotif) {
                continue;
            }

            if (! $dryRun) {
                Notification::send($admins, new VacacionesPendientesNotification($trabajador, $trabajador->saldo_vacaciones));
            }

            $riesgo = $trabajador->saldo_vacaciones > 15 ? ' ⚠️ RIESGO MULTA DT' : '';
            $this->line("  🏖️ {$trabajador->nombres} {$trabajador->apellidos}: {$trabajador->saldo_vacaciones} días pendientes{$riesgo}");
            $count++;
        }

        return $count;
    }

    private function verificarLicencias(bool $dryRun): int
    {
        $hoy = Carbon::today();
        $en7Dias = $hoy->copy()->addDays(7);

        // 1. Licencias por vencer en 7 días
        $porVencer = LicenciaMedica::with('trabajador')
            ->whereIn('estado', ['Pendiente', 'Validada'])
            ->where('fecha_fin', '<=', $en7Dias->toDateString())
            ->where('fecha_fin', '>=', $hoy->toDateString())
            ->get();

        // 2. Licencias vencidas sin cerrar (aún vigentes pero fecha_fin pasada)
        $vencidas = LicenciaMedica::with('trabajador')
            ->whereIn('estado', ['Pendiente', 'Validada'])
            ->where('fecha_fin', '<', $hoy->toDateString())
            ->get();

        $count = 0;

        foreach ($porVencer as $licencia) {
            $admins = $this->obtenerAdmins();
            if ($admins->isEmpty()) {
                continue;
            }

            // Evitar duplicados: verificar si ya notificamos esta licencia por vencer
            $yaNotificado = DatabaseNotification::query()
                ->where('type', LicenciaMedicaAlertaNotification::class)
                ->where('data->licencia_id', $licencia->id)
                ->where('data->tipo_alerta', 'por_vencer')
                ->where('created_at', '>=', Carbon::now()->subDays(1))
                ->exists();

            if ($yaNotificado) {
                continue;
            }

            if (! $dryRun) {
                Notification::send($admins, new LicenciaMedicaAlertaNotification($licencia, 'por_vencer'));
            }

            $dias = $hoy->diffInDays($licencia->fecha_fin, false);
            $this->line("  🏥 Licencia {$licencia->id} ({$licencia->trabajador->nombres} {$licencia->trabajador->apellidos}) - por vencer en {$dias} días");
            $count++;
        }

        foreach ($vencidas as $licencia) {
            $admins = $this->obtenerAdmins();
            if ($admins->isEmpty()) {
                continue;
            }

            // Notificar cada 3 días mientras siga vencida sin cerrar
            $ultimaNotif = DatabaseNotification::query()
                ->where('type', LicenciaMedicaAlertaNotification::class)
                ->where('data->licencia_id', $licencia->id)
                ->where('data->tipo_alerta', 'vencida_sin_cierre')
                ->where('created_at', '>=', Carbon::now()->subDays(3))
                ->exists();

            if ($ultimaNotif) {
                continue;
            }

            if (! $dryRun) {
                Notification::send($admins, new LicenciaMedicaAlertaNotification($licencia, 'vencida_sin_cierre'));
            }

            $diasAtraso = abs($hoy->diffInDays($licencia->fecha_fin, false));
            $this->line("  ⚠️ Licencia {$licencia->id} ({$licencia->trabajador->nombres} {$licencia->trabajador->apellidos}) - vencida hace {$diasAtraso} días sin cerrar");
            $count++;
        }

        return $count;
    }

    private function obtenerAdmins(): Collection
    {
        return User::role('administrador')->get();
    }
}
