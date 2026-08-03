<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\MapEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class WebApiSyncTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_profil_api_matches_web_profil_data(): void
    {
        $this->createProfilSekolah([
            'nama_sekolah' => 'SD Negeri Warialau',
            'kepala_sekolah' => 'Bapak Kepala',
            'alamat' => 'Alamat Profil',
            'kontak' => '0812000000',
        ]);

        Setting::updateOrCreate(['key' => 'alamat_sekolah'], ['value' => 'Alamat Setting', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'no_telp'], ['value' => '0812111111', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'email_sekolah'], ['value' => 'info@sd.test', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'latitude'], ['value' => '-5.7725000', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'longitude'], ['value' => '134.5833000', 'type' => 'text']);

        $api = $this->getJson('/api/v1/profil-sekolah')->assertOk()->json();
        $web = $this->get('/profil')->assertOk();

        $this->assertSame('SD Negeri Warialau', $api['nama_sekolah']);
        $this->assertSame('Alamat Setting', $api['alamat']);
        $this->assertSame('0812111111', $api['kontak']);
        $this->assertSame('info@sd.test', $api['email_sekolah']);
        $this->assertSame('-5.7725000', $api['latitude']);
        $this->assertSame('134.5833000', $api['longitude']);
        $this->assertNotEmpty($api['maps_embed']);
        $this->assertStringContainsString('openstreetmap.org', $api['maps_embed']);

        $web->assertSee('SD Negeri Warialau', false);
        $web->assertSee('Alamat Setting', false);
        $web->assertSee('0812111111', false);
        $web->assertSee('info@sd.test', false);
        $web->assertSee('openstreetmap.org', false);
    }

    public function test_guru_api_matches_active_guru_on_web_source(): void
    {
        $aktif = $this->createGuru(['nama' => 'Guru Aktif Sync', 'status' => 'aktif']);
        $this->createGuru(['nama' => 'Guru Nonaktif Sync', 'status' => 'nonaktif']);

        $api = $this->getJson('/api/v1/guru')->assertOk()->json();

        $this->assertCount(1, $api);
        $this->assertSame($aktif->nama, $api[0]['nama']);
        $this->assertSame('aktif', $api[0]['status']);
    }

    public function test_berita_api_matches_published_web_content(): void
    {
        $admin = $this->createAdmin();
        $published = $this->createBerita($admin, ['judul' => 'Berita Sync Publish', 'status' => 'publish']);
        $this->createBerita($admin, ['judul' => 'Berita Draft Sync', 'status' => 'draft']);

        $apiIndex = $this->getJson('/api/v1/berita')->assertOk()->json();
        $this->assertSame(1, $apiIndex['total']);
        $this->assertSame($published->judul, $apiIndex['data'][0]['judul']);

        $apiShow = $this->getJson('/api/v1/berita/' . $published->id)->assertOk()->json();
        $webShow = $this->get('/berita/' . $published->id)->assertOk();

        $this->assertSame($published->judul, $apiShow['judul']);
        $webShow->assertSee($published->judul, false);
    }

    public function test_banner_and_galeri_api_sync_with_database_source(): void
    {
        $admin = $this->createAdmin();
        $this->createBanner(['judul' => 'Banner Sync', 'status' => 'aktif', 'urutan' => 1]);
        $this->createBanner(['judul' => 'Banner Off', 'status' => 'nonaktif', 'urutan' => 2]);
        $this->createGaleri($admin, ['judul' => 'Galeri Sync']);

        $banners = $this->getJson('/api/v1/banner')->assertOk()->json();
        $galeri = $this->getJson('/api/v1/galeri')->assertOk()->json();

        $this->assertCount(1, $banners);
        $this->assertSame('Banner Sync', $banners[0]['judul']);
        $this->assertSame(1, $galeri['total']);
        $this->assertSame('Galeri Sync', $galeri['data'][0]['judul']);
    }

    public function test_map_embed_helper_builds_consistent_iframe(): void
    {
        $embed = MapEmbed::iframe(-5.7725, 134.5833);
        $url = MapEmbed::googleMapsUrl(-5.7725, 134.5833);

        $this->assertNotNull($embed);
        $this->assertStringContainsString('iframe', $embed);
        $this->assertStringContainsString('openstreetmap.org', $embed);
        $this->assertStringContainsString('google.com/maps', $url);
    }

    public function test_notifikasi_api_available_for_mobile_sync(): void
    {
        $user = $this->createUser();
        $this->createNotifikasi($user, ['judul' => 'Notif Sync', 'dibaca' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifikasi/unread-count')
            ->assertOk()
            ->assertJsonPath('unread', 1);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/notifikasi')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.judul', 'Notif Sync');
    }
}
