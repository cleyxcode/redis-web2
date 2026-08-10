<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Pendaftaran;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendaftaranStatusMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Pendaftaran $pendaftaran) {}

    public function envelope(): Envelope
    {
        $status = $this->pendaftaran->status;
        $subject = match ($status) {
            'diterima' => 'Pendaftaran Diterima — '.$this->pendaftaran->nama_anak,
            'ditolak' => 'Pendaftaran Ditolak — '.$this->pendaftaran->nama_anak,
            default => 'Update Pendaftaran — '.$this->pendaftaran->nama_anak,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pendaftaran-status',
            with: [
                'pendaftaran' => $this->pendaftaran,
                'statusLabel' => match ($this->pendaftaran->status) {
                    'diterima' => 'Diterima',
                    'ditolak' => 'Ditolak',
                    default => 'Menunggu Verifikasi',
                },
            ],
        );
    }
}
