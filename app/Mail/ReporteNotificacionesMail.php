<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReporteNotificacionesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $pdf,
        public string $nombreArchivo,
        public string $periodo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reporte {$this->periodo} de notificaciones - ERP ELEAM",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-notificaciones',
            with: ['periodo' => $this->periodo],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdf, $this->nombreArchivo)
                ->withMime('application/pdf'),
        ];
    }
}
