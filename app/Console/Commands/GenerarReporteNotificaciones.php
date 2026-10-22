<?php

namespace App\Console\Commands;

use App\Mail\ReporteNotificacionesMail;
use App\Models\Contrato;
use App\Models\LicenciaMedica;
use App\Models\Trabajador;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class GenerarReporteNotificaciones extends Command
{
    protected $signature = 'reporte:notificaciones {--periodo=semanal : semanal | mensual} {--email : Enviar por email a administradores} {--guardar : Guardar en storage/app/reportes/}';

    protected $description = 'Genera reporte PDF de notificaciones (contratos, vacaciones, licencias)';

    public function handle(): int
    {
        $periodo = (string) $this->option('periodo');
        $enviarEmail = (bool) $this->option('email');
        $guardar = (bool) $this->option('guardar');

        if (! in_array($periodo, ['semanal', 'mensual'], true)) {
            $this->error('El período debe ser "semanal" o "mensual".');

            return self::FAILURE;
        }

        $hoy = Carbon::today();
        $inicioPeriodo = $periodo === 'mensual'
            ? $hoy->copy()->startOfMonth()
            : $hoy->copy()->startOfWeek();

        $this->info("📊 Generando reporte {$periodo} de notificaciones ({$inicioPeriodo->format('d/m/Y')} - {$hoy->format('d/m/Y')})...");

        // Recopilar datos
        $datos = $this->recopilarDatos($inicioPeriodo, $hoy);

        // Generar PDF una sola vez y reutilizar el contenido.
        $pdf = Pdf::loadView('pdf.reporte-notificaciones', [
            'periodo' => $periodo,
            'fechaGeneracion' => $hoy->format('d/m/Y H:i'),
            'inicioPeriodo' => $inicioPeriodo->format('d/m/Y'),
            'finPeriodo' => $hoy->format('d/m/Y'),
            'datos' => $datos,
        ])->setPaper('A4', 'portrait');

        $contenidoPdf = $pdf->output();
        $nombreArchivo = "reporte-notificaciones-{$periodo}-{$hoy->format('Y-m-d')}.pdf";

        // Guardar en storage si se solicita
        if ($guardar) {
            $ruta = storage_path("app/reportes/{$nombreArchivo}");
            File::ensureDirectoryExists(dirname($ruta));
            File::put($ruta, $contenidoPdf);
            $this->info("💾 Guardado en: {$ruta}");
        }

        // Enviar por email si se solicita
        if ($enviarEmail) {
            $this->enviarPorEmail($contenidoPdf, $nombreArchivo, $periodo);
        }

        // Mostrar resumen en consola
        $this->mostrarResumen($datos);

        $this->info("✅ Reporte generado: {$nombreArchivo}");

        return Command::SUCCESS;
    }

    private function recopilarDatos(Carbon $inicio, Carbon $fin): array
    {
        // 1. Contratos por vencer en el período
        $contratos = Contrato::with('trabajador')
            ->whereNotNull('fecha_termino')
            ->where('fecha_termino', '>=', $inicio->toDateString())
            ->where('fecha_termino', '<=', $fin->copy()->addDays(30)->toDateString()) // mirar 30 días adelante
            ->orderBy('fecha_termino')
            ->get()
            ->map(function ($contrato) {
                $dias = Carbon::today()->diffInDays($contrato->fecha_termino, false);

                return [
                    'trabajador' => "{$contrato->trabajador->nombres} {$contrato->trabajador->apellidos}",
                    'rut' => $contrato->trabajador->rut,
                    'cargo' => $contrato->trabajador->cargo,
                    'tipo_contrato' => $contrato->tipo_contrato,
                    'fecha_termino' => $contrato->fecha_termino->format('d/m/Y'),
                    'dias_restantes' => $dias,
                    'urgencia' => $dias <= 7 ? 'ALTA' : ($dias <= 15 ? 'MEDIA' : 'BAJA'),
                ];
            })
            ->toArray();

        // 2. Trabajadores con vacaciones pendientes (>10 días)
        $vacaciones = Trabajador::conVacacionesPendientesMayorA(10)
            ->map(function ($trabajador) {
                return [
                    'trabajador' => "{$trabajador->nombres} {$trabajador->apellidos}",
                    'rut' => $trabajador->rut,
                    'cargo' => $trabajador->cargo,
                    'dias_pendientes' => $trabajador->saldo_vacaciones,
                    'riesgo_multa' => $trabajador->saldo_vacaciones > 15,
                    'fecha_ingreso' => $trabajador->fecha_ingreso?->format('d/m/Y'),
                ];
            })
            ->toArray();

        // 3. Licencias médicas: activas por vencer / vencidas sin cerrar
        $licenciasPorVencer = LicenciaMedica::with('trabajador')
            ->whereIn('estado', ['Pendiente', 'Validada'])
            ->where('fecha_fin', '>=', Carbon::today()->toDateString())
            ->where('fecha_fin', '<=', Carbon::today()->addDays(7)->toDateString())
            ->orderBy('fecha_fin')
            ->get()
            ->map(function ($licencia) {
                $dias = Carbon::today()->diffInDays($licencia->fecha_fin, false);

                return [
                    'trabajador' => "{$licencia->trabajador->nombres} {$licencia->trabajador->apellidos}",
                    'rut' => $licencia->trabajador->rut,
                    'tipo' => $licencia->tipo,
                    'entidad' => $licencia->entidad_emisora,
                    'fecha_fin' => $licencia->fecha_fin->format('d/m/Y'),
                    'dias_restantes' => $dias,
                    'dias_reposo' => $licencia->dias_reposo,
                ];
            })
            ->toArray();

        $licenciasVencidas = LicenciaMedica::with('trabajador')
            ->whereIn('estado', ['Pendiente', 'Validada'])
            ->where('fecha_fin', '<', Carbon::today()->toDateString())
            ->orderBy('fecha_fin')
            ->get()
            ->map(function ($licencia) {
                $diasAtraso = abs(Carbon::today()->diffInDays($licencia->fecha_fin, false));

                return [
                    'trabajador' => "{$licencia->trabajador->nombres} {$licencia->trabajador->apellidos}",
                    'rut' => $licencia->trabajador->rut,
                    'tipo' => $licencia->tipo,
                    'fecha_fin' => $licencia->fecha_fin->format('d/m/Y'),
                    'dias_atraso' => $diasAtraso,
                    'dias_reposo' => $licencia->dias_reposo,
                ];
            })
            ->toArray();

        // 4. Resumen de notificaciones enviadas en el período (desde BD notifications)
        $notificacionesEnviadas = [];
        try {
            $notificacionesEnviadas = DB::table('notifications')
                ->where('created_at', '>=', $inicio)
                ->where('created_at', '<=', $fin)
                ->get()
                ->groupBy('type')
                ->map(fn ($g) => $g->count())
                ->toArray();
        } catch (\Throwable) {
            // Tabla notifications no existe aún
        }

        return [
            'contratos' => $contratos,
            'vacaciones' => $vacaciones,
            'licencias_por_vencer' => $licenciasPorVencer,
            'licencias_vencidas' => $licenciasVencidas,
            'notificaciones_enviadas' => $notificacionesEnviadas,
            'totales' => [
                'contratos' => count($contratos),
                'vacaciones' => count($vacaciones),
                'licencias_por_vencer' => count($licenciasPorVencer),
                'licencias_vencidas' => count($licenciasVencidas),
                'notificaciones_enviadas' => array_sum($notificacionesEnviadas),
            ],
        ];
    }

    private function mostrarResumen(array $datos): void
    {
        $t = $datos['totales'];

        $this->table(
            ['Categoría', 'Cantidad'],
            [
                ['Contratos por vencer (30 días)', $t['contratos']],
                ['Vacaciones pendientes (>10d)', $t['vacaciones']],
                ['Licencias por vencer (7 días)', $t['licencias_por_vencer']],
                ['Licencias vencidas sin cerrar', $t['licencias_vencidas']],
                ['Notificaciones enviadas en período', $t['notificaciones_enviadas']],
            ]
        );

        // Alertas críticas
        $criticos = array_filter($datos['contratos'], fn ($c) => $c['urgencia'] === 'ALTA');
        $riesgoMulta = array_filter($datos['vacaciones'], fn ($v) => $v['riesgo_multa']);
        $licVencidas = count($datos['licencias_vencidas']);

        if (! empty($criticos)) {
            $this->warn('⚠️  '.count($criticos).' contratos con URGENCIA ALTA (≤7 días)');
        }
        if (! empty($riesgoMulta)) {
            $this->warn('⚠️  '.count($riesgoMulta).' trabajadores con RIESGO DE MULTA DT (>15 días vacaciones)');
        }
        if ($licVencidas > 0) {
            $this->warn("⚠️  {$licVencidas} licencias médicas vencidas sin cerrar");
        }
    }

    private function enviarPorEmail(string $pdf, string $nombreArchivo, string $periodo): void
    {
        $destinatarios = User::role('administrador')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($destinatarios->isEmpty()) {
            $this->warn('📧 No hay administradores con correo configurado para enviar el reporte.');

            return;
        }

        Mail::to($destinatarios->all())->send(
            new ReporteNotificacionesMail($pdf, $nombreArchivo, $periodo)
        );

        $this->info('📧 Reporte enviado a: '.$destinatarios->implode(', '));
    }
}
