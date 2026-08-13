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
        $nomor = $pendaftaran->nomor_registrasi
            ? ' (No. '.$pendaftaran->nomor_registrasi.')'
            : '';

        $this->notifikasi->notifyUser(
            (int) $pendaftaran->user_id,
            'Pendaftaran Diterima Sistem',
            'Pendaftaran atas nama '.$pendaftaran->nama_anak.$nomor.' berhasil diajukan dan menunggu verifikasi sekolah.',
            'pendaftaran',
            (int) $pendaftaran->id,
        );
    }

    /**
     * Saat status berubah: notifikasi in-app + push FCM + email (kecuali re-submit ke pending).
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

            return;
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

            return;
        }

        if ($status === 'perlu_perbaikan') {
            $pesan = 'Pendaftaran atas nama '.$pendaftaran->nama_anak.' perlu diperbaiki.';
            if ($catatan !== '') {
                $pesan .= ' Catatan: '.$catatan;
            } else {
                $pesan .= ' Silakan unggah ulang dokumen yang diminta.';
            }

            $this->notifikasi->notifyUser(
                (int) $pendaftaran->user_id,
                'Perlu Perbaikan Dokumen',
                $pesan,
                'pendaftaran',
                (int) $pendaftaran->id,
            );
            $this->sendStatusEmail($pendaftaran);

            return;
        }

        if ($status === 'pending' && $pendaftaran->getOriginal('status') === 'perlu_perbaikan') {
            $this->notifikasi->notifyUser(
                (int) $pendaftaran->user_id,
                'Dokumen Dikirim Ulang',
                'Dokumen untuk '.$pendaftaran->nama_anak.' telah dikirim ulang dan menunggu verifikasi ulang.',
                'pendaftaran',
                (int) $pendaftaran->id,
            );
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

            Mail::to($user->email)->queue(new PendaftaranStatusMail($pendaftaran));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
