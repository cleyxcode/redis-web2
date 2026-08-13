<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class NotifikasiApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_notifikasi_requires_auth(): void
    {
        $this->getJson('/api/v1/notifikasi')->assertUnauthorized();
        $this->getJson('/api/v1/notifikasi/unread-count')->assertUnauthorized();
    }

    public function test_list_unread_count_and_mark_read(): void
    {
        $user = $this->createUser();
        $n1 = $this->createNotifikasi($user, ['judul' => 'Satu']);
        $this->createNotifikasi($user, ['judul' => 'Dua']);
        Sanctum::actingAs($user);

        $list = $this->getJson('/api/v1/notifikasi')->assertOk();
        $this->assertSame(2, $list->json('total'));
        $this->assertIsBool($list->json('data.0.dibaca'));
        $this->assertIsInt($list->json('data.0.id'));
        $this->assertIsInt($list->json('data.0.user_id'));

        $this->getJson('/api/v1/notifikasi/unread-count')
            ->assertOk()
            ->assertJsonPath('unread', 2);

        $this->patchJson('/api/v1/notifikasi/'.$n1->id.'/baca')
            ->assertOk()
            ->assertJsonPath('message', 'Notifikasi ditandai sudah dibaca.');

        $this->getJson('/api/v1/notifikasi/unread-count')
            ->assertJsonPath('unread', 1);

        $this->patchJson('/api/v1/notifikasi/baca-semua')
            ->assertOk()
            ->assertJsonPath('message', 'Semua notifikasi ditandai sudah dibaca.');

        $this->getJson('/api/v1/notifikasi/unread-count')
            ->assertJsonPath('unread', 0);
    }

    public function test_mark_missing_notifikasi_returns_404_json(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/notifikasi/999999/baca')
            ->assertNotFound()
            ->assertJsonPath('message', 'Notifikasi tidak ditemukan.');
    }

    public function test_trailing_slash_list_still_works(): void
    {
        $user = $this->createUser();
        $this->createNotifikasi($user, ['judul' => 'Trailing']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/notifikasi/')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.judul', 'Trailing');
    }

    public function test_user_can_delete_own_notifikasi(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $own = $this->createNotifikasi($user, ['judul' => 'Milik Saya']);
        $foreign = $this->createNotifikasi($other, ['judul' => 'Milik Orang']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/notifikasi/'.$own->id)
            ->assertOk()
            ->assertJsonPath('message', 'Notifikasi dihapus.');

        $this->assertDatabaseMissing('notifikasi', ['id' => $own->id]);

        $this->deleteJson('/api/v1/notifikasi/'.$foreign->id)
            ->assertNotFound();

        $this->assertDatabaseHas('notifikasi', ['id' => $foreign->id]);
    }
}
