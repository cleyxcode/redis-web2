<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_profil_sekolah_endpoint(): void
    {
        $this->createProfilSekolah(['nama_sekolah' => 'SD Negeri Warialau']);

        $this->getJson('/api/v1/profil-sekolah')
            ->assertOk()
            ->assertJsonPath('nama_sekolah', 'SD Negeri Warialau');
    }

    public function test_banner_endpoint(): void
    {
        $this->createBanner(['judul' => 'Welcome']);
        $this->createBanner(['judul' => 'Inactive', 'status' => 'nonaktif', 'urutan' => 2]);

        $response = $this->getJson('/api/v1/banner')->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('Welcome', $response->json('0.judul'));
    }

    public function test_guru_endpoint_returns_active_only(): void
    {
        $this->createGuru(['nama' => 'Aktif']);
        $this->createGuru(['nama' => 'Nonaktif', 'status' => 'nonaktif']);

        $response = $this->getJson('/api/v1/guru')->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('Aktif', $response->json('0.nama'));
    }

    public function test_berita_index_and_show(): void
    {
        $admin = $this->createAdmin();
        $berita = $this->createBerita($admin, ['judul' => 'Berita API']);
        $this->createBerita($admin, ['judul' => 'Draft', 'status' => 'draft']);

        $index = $this->getJson('/api/v1/berita')->assertOk();
        $this->assertSame(1, $index->json('total'));

        $this->getJson('/api/v1/berita/' . $berita->id)
            ->assertOk()
            ->assertJsonPath('judul', 'Berita API');

        $this->getJson('/api/v1/berita/99999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Berita tidak ditemukan.');
    }

    public function test_galeri_endpoint(): void
    {
        $admin = $this->createAdmin();
        $this->createGaleri($admin, ['judul' => 'Foto 1']);

        $response = $this->getJson('/api/v1/galeri')->assertOk();
        $this->assertSame(1, $response->json('total'));
        $this->assertSame('Foto 1', $response->json('data.0.judul'));
    }

    public function test_info_pendaftaran_aktif_and_closed(): void
    {
        $this->getJson('/api/v1/info-pendaftaran')
            ->assertNotFound()
            ->assertJsonPath('message', 'Pendaftaran sedang tidak dibuka.');

        $admin = $this->createAdmin();
        $this->createInfoPendaftaran($admin, ['tahun_ajaran' => '2026/2027']);

        $this->getJson('/api/v1/info-pendaftaran')
            ->assertOk()
            ->assertJsonPath('tahun_ajaran', '2026/2027');
    }
}
