<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\AplikasiResource\Pages\ListAplikasi;
use App\Filament\Resources\BannerResource\Pages\ListBanners;
use App\Filament\Resources\BeritaResource\Pages\ListBeritas;
use App\Filament\Resources\FaqResource\Pages\ListFaqs;
use App\Filament\Resources\GaleriResource\Pages\ListGaleris;
use App\Filament\Resources\GuruResource\Pages\ListGurus;
use App\Filament\Resources\InfoPendaftaranResource\Pages\ListInfoPendaftarans;
use App\Filament\Resources\PendaftaranResource;
use App\Filament\Resources\PendaftaranResource\Pages\ListPendaftarans;
use App\Filament\Resources\PendaftaranResource\Pages\ViewPendaftaran;
use App\Filament\Resources\ProfilSekolahResource\Pages\EditProfilSekolah;
use App\Filament\Resources\SettingResource\Pages\EditAllSettings;
use App\Filament\Resources\SiswaResource\Pages\ListSiswas;
use App\Filament\Resources\TonggakSejarahResource\Pages\ListTonggakSejarahs;
use App\Models\ProfilSekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminPanelResourcesTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_admin_can_open_all_key_resource_lists(): void
    {
        $admin = $this->createAdmin();
        $profil = $this->createProfilSekolah();
        $this->createFaq();
        $this->createTonggakSejarah();
        $this->createGuru();
        $this->createBanner();
        $this->createBerita($admin);
        $this->createGaleri($admin);
        $this->createInfoPendaftaran($admin);
        $this->createSiswa();

        // Resource list pages yang dipakai menu admin sehari-hari
        $pages = [
            ListFaqs::class,
            ListTonggakSejarahs::class,
            ListGurus::class,
            ListBeritas::class,
            ListGaleris::class,
            ListBanners::class,
            ListInfoPendaftarans::class,
            ListPendaftarans::class,
            ListSiswas::class,
            ListAplikasi::class,
            ListActivityLogs::class,
        ];

        foreach ($pages as $page) {
            Livewire::actingAs($admin)
                ->test($page)
                ->assertSuccessful();
        }

        // Profil & setting memakai halaman edit khusus (bukan list generik)
        Livewire::actingAs($admin)
            ->test(EditProfilSekolah::class, ['record' => $profil->getRouteKey()])
            ->assertSuccessful();

        Livewire::actingAs($admin)
            ->test(EditAllSettings::class)
            ->assertSuccessful();

        $this->assertInstanceOf(ProfilSekolah::class, $profil);
    }

    public function test_admin_can_create_guru_and_siswa(): void
    {
        $admin = $this->createAdmin();

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\GuruResource\Pages\CreateGuru::class)
            ->fillForm([
                'nama' => 'Guru Baru',
                'nip' => '199001012020011001',
                'jabatan' => 'Guru Kelas',
                'mata_pelajaran' => 'Bahasa Indonesia',
                'no_hp' => '081234567890',
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('guru', [
            'nama' => 'Guru Baru',
            'status' => 'aktif',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\SiswaResource\Pages\CreateSiswa::class)
            ->fillForm([
                'nama' => 'Siswa Baru',
                'nis' => '2026999',
                'kelas' => '1A',
                'jenis_kelamin' => 'L',
                'tahun_ajaran' => '2026/2027',
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('siswa', [
            'nama' => 'Siswa Baru',
            'nis' => '2026999',
        ]);
    }

    public function test_pendaftaran_navigation_badge_reflects_pending(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        $this->assertSame('1', PendaftaranResource::getNavigationBadge());
        $this->assertSame('warning', PendaftaranResource::getNavigationBadgeColor());
        $this->assertStringContainsString('menunggu verifikasi', (string) PendaftaranResource::getNavigationBadgeTooltip());
        $this->assertFalse(PendaftaranResource::canCreate());
    }

    public function test_admin_can_view_pendaftaran_detail(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, [
            'nama_anak' => 'Anak View',
            'nomor_registrasi' => 'PPDB-VIEW-0001',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewPendaftaran::class, ['record' => $pendaftaran->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Anak View')
            ->assertSee('PPDB-VIEW-0001');
    }
}
