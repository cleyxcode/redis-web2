<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotifikasiService
{
    public function __construct(
        private NotifikasiRepositoryInterface $repo,
        private FcmPushService $fcm,
    ) {}

    public function getAll(int $userId, int $perPage = 20): LengthAwarePaginator
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

    public function deleteForUser(int $id, int $userId): bool
    {
        return $this->repo->deleteForUser($id, $userId);
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

        $notifikasi = Notifikasi::query()->create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'referensi_id' => $referensiId,
            'dibaca' => false,
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

    /**
     * Bulk in-app notifications (no per-row FCM — use sendToUser separately if needed).
     *
     * @param  Collection<int, int|string>|array<int, int|string>  $userIds
     */
    public function notifyMany(
        Collection|array $userIds,
        string $judul,
        string $pesan,
        string $tipe = 'umum',
        ?int $referensiId = null,
    ): int {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $ids->map(fn (int $userId) => [
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'referensi_id' => $referensiId,
            'dibaca' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notifikasi::query()->insert($rows);

        return count($rows);
    }
}
