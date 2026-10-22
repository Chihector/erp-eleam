<?php

namespace App\Notifications;

use App\Models\LicenciaMedica;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenciaMedicaAlertaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LicenciaMedica $licencia,
        public string $tipoAlerta // 'por_vencer', 'vencida_sin_cierre', 'nueva'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trabajador = $this->licencia->trabajador;
        $hoy = Carbon::today();

        $mensaje = match ($this->tipoAlerta) {
            'por_vencer' => "La licencia vence en **{$this->licencia->fecha_fin->diffInDays($hoy)} días** ({$this->licencia->fecha_fin->format('d/m/Y')}).",
            'vencida_sin_cierre' => "La licencia venció el **{$this->licencia->fecha_fin->format('d/m/Y')}** y aún no se ha cerrado. Días de retraso: {$hoy->diffInDays($this->licencia->fecha_fin)}.",
            'nueva' => "Nueva licencia médica registrada: **{$this->licencia->tipo}** desde {$this->licencia->fecha_inicio->format('d/m/Y')} hasta {$this->licencia->fecha_fin->format('d/m/Y')}.",
            default => 'Actualización en licencia médica.',
        };

        $asunto = match ($this->tipoAlerta) {
            'por_vencer' => "🏥 Licencia médica por vencer: {$trabajador->nombres} {$trabajador->apellidos}",
            'vencida_sin_cierre' => "⚠️ Licencia médica vencida sin cerrar: {$trabajador->nombres} {$trabajador->apellidos}",
            'nueva' => "📋 Nueva licencia médica: {$trabajador->nombres} {$trabajador->apellidos}",
            default => "Licencia médica: {$trabajador->nombres} {$trabajador->apellidos}",
        };

        return (new MailMessage)
            ->subject($asunto)
            ->greeting('Hola,')
            ->line("Trabajador: **{$trabajador->nombres} {$trabajador->apellidos}** (RUT: {$trabajador->rut})")
            ->line("Tipo de licencia: {$this->licencia->tipo}")
            ->line("Entidad emisora: {$this->licencia->entidad_emisora}")
            ->line("Período: {$this->licencia->fecha_inicio->format('d/m/Y')} - {$this->licencia->fecha_fin->format('d/m/Y')} ({$this->licencia->dias_reposo} días)")
            ->line($mensaje)
            ->action('Ver licencia', url("/admin/licencia-medicas/{$this->licencia->id}/edit"))
            ->line('Saludos,')
            ->line('Sistema ERP ELEAM');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'licencia_medica_alerta',
            'licencia_id' => $this->licencia->id,
            'tipo_alerta' => $this->tipoAlerta,
            'trabajador_id' => $this->licencia->trabajador_id,
            'trabajador_nombre' => "{$this->licencia->trabajador->nombres} {$this->licencia->trabajador->apellidos}",
            'fecha_fin' => $this->licencia->fecha_fin->format('Y-m-d'),
            'dias_reposo' => $this->licencia->dias_reposo,
            'mensaje' => match ($this->tipoAlerta) {
                'por_vencer' => "Por vencer en {$this->licencia->fecha_fin->diffInDays(Carbon::today())} días",
                'vencida_sin_cierre' => 'Vencida hace '.Carbon::today()->diffInDays($this->licencia->fecha_fin).' días',
                'nueva' => 'Nueva licencia registrada',
                default => 'Alerta de licencia médica',
            },
        ];
    }
}
