<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Berita;
use App\Models\User;
use App\Services\FcmPushService;
use App\Services\NotifikasiService;
use Illuminate\Support\Facades\Cache;

class BeritaObserver
{
    public function __construct(
        private NotifikasiService $notifikasi,
        private FcmPushService $fcm,
    ) {}

    /**
     * Saat berita diupdate: jika status berubah menjadi 'publish',
     * buat notifikasi untuk semua user orangtua.
     */
    public function updated(Berita $berita): void
    {
        Cache::forget("berita_{$berita->id}");
        Cache::forget('berita_latest_5');
        Cache::forget('berita_latest_3');

        if ($berita->wasChanged('status') && $berita->status === 'publish') {
            $this->notifyOrangtua($berita);
        }
    }

    public function created(Berita $berita): void
    {
        Cache::forget('berita_latest_5');
        Cache::forget('berita_latest_3');

        if ($berita->status === 'publish') {
            $this->notifyOrangtua($berita);
        }
    }

    public function deleted(Berita $berita): void
    {
        Cache::forget("berita_{$berita->id}");
        Cache::forget('berita_latest_5');
        Cache::forget('berita_latest_3');
    }

    private function notifyOrangtua(Berita $berita): void
    {
        $orangtuaIds = User::query()
            ->where('role', 'orangtua')
            ->pluck('id');

        $judul = 'Berita Baru';
        $pesan = 'Berita baru telah diterbitkan: '.$berita->judul;

        $this->notifikasi->notifyMany(
            $orangtuaIds,
            $judul,
            $pesan,
            'berita',
            (int) $berita->id,
        );

        foreach ($orangtuaIds as $userId) {
            $this->fcm->sendToUser((int) $userId, $judul, $pesan, [
                'tipe' => 'berita',
                'referensi_id' => (string) $berita->id,
                'title' => $judul,
                'body' => $pesan,
            ]);
        }
    }
}
