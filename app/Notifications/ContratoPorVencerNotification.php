<?php

namespace App\Notifications;

use App\Models\Contrato;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContratoPorVencerNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contrato $contrato,
        public int $diasRestantes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trabajador = $this->contrato->trabajador;
        $dias = $this->diasRestantes;

        $mensaje = match (true) {
            $dias <= 7 => "El contrato vence en **{$dias} días** ({$this->contrato->fecha_termino->format('d/m/Y')}). ¡Acción urgente requerida!",
            $dias <= 15 => "El contrato vence en **{$dias} días** ({$this->contrato->fecha_termino->format('d/m/Y')}). Planifique renovación o finiquito.",
            default => "El contrato vence en **{$dias} días** ({$this->contrato->fecha_termino->format('d/m/Y')}).",
        };

        return (new MailMessage)
            ->subject("⚠️ Contrato por vencer: {$trabajador->nombres} {$trabajador->apellidos} ({$trabajador->rut})")
            ->greeting('Hola,')
            ->line("El contrato de **{$trabajador->nombres} {$trabajador->apellidos}** (RUT: {$trabajador->rut}) está próximo a vencer.")
            ->line($mensaje)
            ->line("Cargo: {$trabajador->cargo}")
            ->line("Tipo de contrato: {$this->contrato->tipo_contrato}")
            ->action('Ver en ERP ELEAM', url("/admin/trabajadors/{$trabajador->id}/edit"))
            ->line('Saludos,')
            ->line('Sistema ERP ELEAM');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'contrato_por_vencer',
            'contrato_id' => $this->contrato->id,
            'trabajador_id' => $this->contrato->trabajador_id,
            'trabajador_nombre' => "{$this->contrato->trabajador->nombres} {$this->contrato->trabajador->apellidos}",
            'trabajador_rut' => $this->contrato->trabajador->rut,
            'fecha_termino' => $this->contrato->fecha_termino->format('Y-m-d'),
            'dias_restantes' => $this->diasRestantes,
            'mensaje' => $this->diasRestantes <= 7
                ? "Vence en {$this->diasRestantes} días - URGENTE"
                : "Vence en {$this->diasRestantes} días",
        ];
    }
}
