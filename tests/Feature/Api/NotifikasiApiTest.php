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
    }

    public function test_list_unread_count_and_mark_read(): void
    {
        $user = $this->createUser();
        $n1 = $this->createNotifikasi($user, ['judul' => 'Satu']);
        $this->createNotifikasi($user, ['judul' => 'Dua']);
        Sanctum::actingAs($user);

        $list = $this->getJson('/api/v1/notifikasi')->assertOk();
        $this->assertSame(2, $list->json('total'));

        $this->getJson('/api/v1/notifikasi/unread-count')
            ->assertOk()
            ->assertJsonPath('unread', 2);

        $this->patchJson('/api/v1/notifikasi/' . $n1->id . '/baca')
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
}
