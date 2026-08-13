<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class NotifikasiWebTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_guest_cannot_access_notifikasi_page(): void
    {
        $this->get('/notifikasi')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_and_mark_notifikasi(): void
    {
        $user = $this->createUser(['role' => 'orangtua']);
        $n1 = $this->createNotifikasi($user, [
            'judul' => 'Pendaftaran Diterima',
            'tipe' => 'umum',
        ]);
        $this->createNotifikasi($user, ['judul' => 'Info Lain']);

        $this->actingAs($user)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Pendaftaran Diterima')
            ->assertSee('Info Lain')
            ->assertSee('2 notifikasi belum dibaca');

        $this->actingAs($user)
            ->patch('/notifikasi/'.$n1->id.'/baca')
            ->assertRedirect();

        $this->assertDatabaseHas('notifikasi', [
            'id' => $n1->id,
            'dibaca' => true,
        ]);

        $this->actingAs($user)
            ->patch('/notifikasi/baca-semua')
            ->assertRedirect();

        $this->assertSame(0, $user->notifikasi()->where('dibaca', false)->count());
    }

    public function test_filter_belum_dibaca(): void
    {
        $user = $this->createUser();
        $this->createNotifikasi($user, ['judul' => 'Belum', 'dibaca' => false]);
        $this->createNotifikasi($user, ['judul' => 'Sudah', 'dibaca' => true]);

        $this->actingAs($user)
            ->get('/notifikasi?filter=belum')
            ->assertOk()
            ->assertSee('Belum')
            ->assertDontSee('Sudah');
    }

    public function test_user_can_delete_notifikasi(): void
    {
        $user = $this->createUser();
        $n = $this->createNotifikasi($user, ['judul' => 'Hapus Saya']);

        $this->actingAs($user)
            ->delete('/notifikasi/'.$n->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('notifikasi', ['id' => $n->id]);
    }
}
