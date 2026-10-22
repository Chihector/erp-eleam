<?php

namespace App\Notifications;

use App\Models\Trabajador;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacacionesPendientesNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Trabajador $trabajador,
        public int $diasPendientes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $riesgo = $this->diasPendientes > 15 ? '⚠️ **RIESGO DE MULTA DT** (superior a 15 días legales)' : 'Pendiente de gestión';

        return (new MailMessage)
            ->subject("📅 Vacaciones pendientes: {$this->trabajador->nombres} {$this->trabajador->apellidos}")
            ->greeting('Hola,')
            ->line("El trabajador **{$this->trabajador->nombres} {$this->trabajador->apellidos}** (RUT: {$this->trabajador->rut}) tiene **{$this->diasPendientes} días de vacaciones legales pendientes**.")
            ->line($riesgo)
            ->line("Cargo: {$this->trabajador->cargo}")
            ->line("Fecha de ingreso: {$this->trabajador->fecha_ingreso?->format('d/m/Y')}")
            ->action('Gestionar vacaciones', url("/admin/trabajadors/{$this->trabajador->id}/edit"))
            ->line('Saludos,')
            ->line('Sistema ERP ELEAM');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'vacaciones_pendientes',
            'trabajador_id' => $this->trabajador->id,
            'trabajador_nombre' => "{$this->trabajador->nombres} {$this->trabajador->apellidos}",
            'trabajador_rut' => $this->trabajador->rut,
            'dias_pendientes' => $this->diasPendientes,
            'riesgo_multa' => $this->diasPendientes > 15,
            'mensaje' => "{$this->diasPendientes} días pendientes".($this->diasPendientes > 15 ? ' - RIESGO MULTA DT' : ''),
        ];
    }
}
