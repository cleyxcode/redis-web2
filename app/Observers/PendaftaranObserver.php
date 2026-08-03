<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Pendaftaran;
use App\Services\NotifikasiService;

class PendaftaranObserver
{
    public function __construct(private NotifikasiService $notifikasi) {}

    /**
     * Saat status pendaftaran berubah menjadi 'diterima' atau 'ditolak',
     * buat notifikasi in-app + push FCM untuk orang tua.
     */
    public function updated(Pendaftaran $pendaftaran): void
    {
        if (! $pendaftaran->wasChanged('status')) {
            return;
        }

        $status = $pendaftaran->status;
        $catatan = trim((string) ($pendaftaran->catatan_verifikasi ?? ''));

        if ($status === 'diterima') {
            $pesan = 'Selamat! Pendaftaran atas nama '.$pendaftaran->nama_anak.' telah diterima.';
            if ($catatan !== '') {
                $pesan .= ' Catatan: '.$catatan;
            }

            $this->notifikasi->notifyUser(
                $pendaftaran->user_id,
                'Pendaftaran Diterima',
                $pesan,
                'pendaftaran',
                $pendaftaran->id,
            );
        }

        if ($status === 'ditolak') {
            $pesan = 'Mohon maaf, pendaftaran atas nama '.$pendaftaran->nama_anak.' tidak dapat diterima.';
            if ($catatan !== '') {
                $pesan .= ' Alasan: '.$catatan;
            }

            $this->notifikasi->notifyUser(
                $pendaftaran->user_id,
                'Pendaftaran Ditolak',
                $pesan,
                'pendaftaran',
                $pendaftaran->id,
            );
        }
    }
}
