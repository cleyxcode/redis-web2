<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Notifikasi;
use App\Repositories\Contracts\NotifikasiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotifikasiRepository implements NotifikasiRepositoryInterface
{
    public function paginate(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notifikasi::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function countUnread(int $userId): int
    {
        return Notifikasi::query()
            ->where('user_id', $userId)
            ->where('dibaca', false)
            ->count();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $notifikasi = Notifikasi::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($notifikasi === null) {
            return false;
        }

        if ($notifikasi->dibaca) {
            return true;
        }

        return (bool) $notifikasi->update(['dibaca' => true]);
    }

    public function markAllAsRead(int $userId): void
    {
        Notifikasi::query()
            ->where('user_id', $userId)
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
    }

    public function deleteForUser(int $id, int $userId): bool
    {
        $deleted = Notifikasi::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();

        return $deleted > 0;
    }
}
