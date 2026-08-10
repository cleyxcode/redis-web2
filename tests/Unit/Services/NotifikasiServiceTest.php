<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class NotifikasiServiceTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_notify_user_creates_unread_notification(): void
    {
        $user = $this->createUser(['role' => 'orangtua']);
        $service = app(NotifikasiService::class);

        $notif = $service->notifyUser(
            $user,
            'Judul Tes',
            'Pesan tes notifikasi',
            'pendaftaran',
            42,
        );

        $this->assertDatabaseHas('notifikasi', [
            'id' => $notif->id,
            'user_id' => $user->id,
            'judul' => 'Judul Tes',
            'tipe' => 'pendaftaran',
            'referensi_id' => 42,
            'dibaca' => false,
        ]);
        $this->assertSame(1, $service->countUnread($user->id));
    }

    public function test_mark_read_and_mark_all_read(): void
    {
        $user = $this->createUser();
        $n1 = $this->createNotifikasi($user, ['judul' => 'Satu']);
        $this->createNotifikasi($user, ['judul' => 'Dua']);
        $service = app(NotifikasiService::class);

        $this->assertSame(2, $service->countUnread($user->id));
        $this->assertTrue($service->markAsRead($n1->id, $user->id));
        $this->assertSame(1, $service->countUnread($user->id));

        $service->markAllAsRead($user->id);
        $this->assertSame(0, $service->countUnread($user->id));
        $this->assertSame(2, $service->getAll($user->id)->total());
    }

    public function test_mark_as_read_returns_false_for_foreign_notification(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $other = $this->createUser(['email' => 'other@test.com']);
        $notif = $this->createNotifikasi($owner);
        $service = app(NotifikasiService::class);

        $this->assertFalse($service->markAsRead($notif->id, $other->id));
        $this->assertFalse($service->markAsRead(999999, $owner->id));
    }

    public function test_notify_many_inserts_rows_for_each_user(): void
    {
        $u1 = $this->createUser(['email' => 'a@test.com', 'role' => 'orangtua']);
        $u2 = $this->createUser(['email' => 'b@test.com', 'role' => 'orangtua']);
        $service = app(NotifikasiService::class);

        $count = $service->notifyMany(
            [$u1->id, $u2->id, $u2->id],
            'Berita Baru',
            'Ada berita baru',
            'berita',
            7,
        );

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $u1->id,
            'tipe' => 'berita',
            'referensi_id' => 7,
            'dibaca' => false,
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $u2->id,
            'tipe' => 'berita',
        ]);
    }
}
