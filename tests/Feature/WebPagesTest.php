<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_home_page_renders_faq_from_database(): void
    {
        $this->createProfilSekolah();
        $this->createFaq([
            'pertanyaan' => 'Apakah ada biaya pendaftaran?',
            'jawaban' => 'Tidak ada biaya pendaftaran.',
            'urutan' => 1,
        ]);
        $this->createFaq([
            'pertanyaan' => 'FAQ nonaktif?',
            'jawaban' => 'Tidak tampil',
            'status' => 'nonaktif',
            'urutan' => 2,
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Apakah ada biaya pendaftaran?', false);
        $response->assertSee('Tidak ada biaya pendaftaran.', false);
        $response->assertDontSee('FAQ nonaktif?', false);
    }

    public function test_profil_page_shows_tujuan_tonggak_and_jam_operasional(): void
    {
        $this->createProfilSekolah([
            'nama_sekolah' => 'SD Negeri Warialau',
            'tujuan' => 'Tujuan khusus pengujian profil',
            'visi' => 'Visi tampil',
            'misi' => "1. Misi satu\n2. Misi dua",
            'sejarah' => 'Sejarah tampil di halaman profil.',
        ]);
        $this->createTonggakSejarah([
            'tahun' => '2024',
            'deskripsi' => 'Laboratorium komputer baru.',
        ]);
        Setting::updateOrCreate(
            ['key' => 'jam_operasional'],
            ['value' => 'Senin – Sabtu 07.30 – 14.00 WIT', 'type' => 'text']
        );

        $response = $this->get('/profil')->assertOk();
        $response->assertSee('Tujuan khusus pengujian profil', false);
        $response->assertSee('Tujuan Sekolah', false);
        $response->assertSee('2024', false);
        $response->assertSee('Laboratorium komputer baru.', false);
        $response->assertSee('Senin – Sabtu 07.30 – 14.00 WIT', false);
        $response->assertSee('Visi tampil', false);
    }

    public function test_public_pages_are_reachable(): void
    {
        $admin = $this->createAdmin();
        $this->createProfilSekolah();
        $this->createGuru();
        $berita = $this->createBerita($admin, ['judul' => 'Berita Halaman Publik']);
        $this->createGaleri($admin);
        $this->createInfoPendaftaran($admin);

        $this->get('/')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/guru')->assertOk()->assertSee('Guru Test', false);
        $this->get('/berita')->assertOk()->assertSee('Berita Halaman Publik', false);
        $this->get('/berita/' . $berita->id)->assertOk()->assertSee('Berita Halaman Publik', false);
        $this->get('/galeri')->assertOk();
        $this->get('/pendaftaran')->assertOk();
        $this->get('/download-aplikasi')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_protected_web_routes_require_auth(): void
    {
        $this->get('/pendaftaran/riwayat')->assertRedirect('/login');
        $this->get('/profil-akun')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_riwayat_and_profil_akun(): void
    {
        $user = $this->createUser();
        $this->createProfilSekolah();
        $info = $this->createInfoPendaftaran($user);
        $this->createPendaftaran($user, $info, ['nama_anak' => 'Anak Riwayat']);

        $this->actingAs($user)
            ->get('/pendaftaran/riwayat')
            ->assertOk()
            ->assertSee('Anak Riwayat', false);

        $this->actingAs($user)
            ->get('/profil-akun')
            ->assertOk();
    }
}
