<?php

declare(strict_types=1);

namespace App\Observers;

use App\Mail\PendaftaranStatusMail;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PendaftaranObserver
{
    public function __construct(private NotifikasiService $notifikasi) {}

    /**
     * Konfirmasi in-app saat orang tua baru mengajukan pendaftaran.
     */
    public function created(Pendaftaran $pendaftaran): void
    {
        $this->notifikasi->notifyUser(
            (int) $pendaftaran->user_id,
            'Pendaftaran Diterima Sistem',
            'Pendaftaran atas nama '.$pendaftaran->nama_anak.' berhasil diajukan dan menunggu verifikasi sekolah.',
            'pendaftaran',
            (int) $pendaftaran->id,
        );
    }

    /**
     * Saat status pendaftaran berubah menjadi 'diterima' atau 'ditolak',
     * buat notifikasi in-app + push FCM + email untuk orang tua.
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
                (int) $pendaftaran->user_id,
                'Pendaftaran Diterima',
                $pesan,
                'pendaftaran',
                (int) $pendaftaran->id,
            );
            $this->sendStatusEmail($pendaftaran);
        }

        if ($status === 'ditolak') {
            $pesan = 'Mohon maaf, pendaftaran atas nama '.$pendaftaran->nama_anak.' tidak dapat diterima.';
            if ($catatan !== '') {
                $pesan .= ' Alasan: '.$catatan;
            }

            $this->notifikasi->notifyUser(
                (int) $pendaftaran->user_id,
                'Pendaftaran Ditolak',
                $pesan,
                'pendaftaran',
                (int) $pendaftaran->id,
            );
            $this->sendStatusEmail($pendaftaran);
        }
    }

    private function sendStatusEmail(Pendaftaran $pendaftaran): void
    {
        try {
            $user = $pendaftaran->relationLoaded('user')
                ? $pendaftaran->user
                : User::query()->find($pendaftaran->user_id);

            if ($user === null || blank($user->email)) {
                return;
            }

            Mail::to($user->email)->send(new PendaftaranStatusMail($pendaftaran));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
