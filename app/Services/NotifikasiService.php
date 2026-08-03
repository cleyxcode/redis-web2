<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;

class NotifikasiService
{
    public function __construct(
        private NotifikasiRepositoryInterface $repo,
        private FcmPushService $fcm,
    ) {}

    public function getAll(int $userId, int $perPage = 20)
    {
        return $this->repo->paginate($userId, $perPage);
    }

    public function countUnread(int $userId): int
    {
        return $this->repo->countUnread($userId);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        return $this->repo->markAsRead($id, $userId);
    }

    public function markAllAsRead(int $userId): void
    {
        $this->repo->markAllAsRead($userId);
    }

    /**
     * @param  array<string, string>  $pushData
     */
    public function notifyUser(
        User|int $user,
        string $judul,
        string $pesan,
        string $tipe = 'umum',
        ?int $referensiId = null,
        array $pushData = [],
    ): Notifikasi {
        $userId = $user instanceof User ? $user->id : $user;

        $notifikasi = Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'referensi_id' => $referensiId,
        ]);

        $this->fcm->sendToUser($userId, $judul, $pesan, array_merge([
            'tipe' => $tipe,
            'referensi_id' => (string) ($referensiId ?? ''),
            'notifikasi_id' => (string) $notifikasi->id,
            'title' => $judul,
            'body' => $pesan,
        ], $pushData));

        return $notifikasi;
    }
}
