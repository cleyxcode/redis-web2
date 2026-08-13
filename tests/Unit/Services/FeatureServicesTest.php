<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BeritaService;
use App\Services\GaleriService;
use App\Services\PendaftaranService;
use App\Services\ProfilSekolahService;
use App\Services\ProfileService;
use App\Services\NotifikasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class FeatureServicesTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_profil_sekolah_service_caches_profil_and_banner(): void
    {
        $this->createProfilSekolah(['nama_sekolah' => 'SD Test']);
        $this->createBanner(['judul' => 'Banner 1']);

        $service = app(ProfilSekolahService::class);
        $profil = $service->getProfil();

        $this->assertSame('SD Test', $profil['nama_sekolah']);
        $this->assertCount(1, $service->getBanner());
        $this->assertTrue(Cache::has('profil_sekolah'));
        $this->assertTrue(Cache::has('banner_aktif'));
    }

    public function test_berita_service_get_by_id_and_latest(): void
    {
        $admin = $this->createAdmin();
        $berita = $this->createBerita($admin, ['judul' => 'Judul A']);
        $this->createBerita($admin, ['judul' => 'Draft', 'status' => 'draft']);

        $service = app(BeritaService::class);

        $this->assertSame('Judul A', $service->getById($berita->id)->judul);
        $this->assertNull($service->getById(99999));
        $this->assertCount(1, $service->getLatest(5));
        $this->assertTrue(Cache::has("berita_{$berita->id}"));
    }

    public function test_galeri_service_paginates_and_caches(): void
    {
        $admin = $this->createAdmin();
        $this->createGaleri($admin);
        $this->createGaleri($admin, ['judul' => 'Galeri 2']);

        $service = app(GaleriService::class);
        $page = $service->getAll(12);

        $this->assertSame(2, $page->total());
        $this->assertTrue(Cache::has('galeri_page_1_per12'));
    }

    public function test_pendaftaran_service_info_riwayat_and_submit(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);

        $service = app(PendaftaranService::class);

        $this->assertSame($info->id, $service->getInfoAktif()->id);
        $this->assertTrue(Cache::has('info_pendaftaran_aktif'));

        $pendaftaran = $service->submit($user, [
            'nama_anak' => 'Anak Baru',
            'tanggal_lahir' => '2019-05-01',
            'jenis_kelamin' => 'P',
            'agama' => 'Islam',
            'alamat' => 'Jl. Merdeka',
            'no_hp' => '081999888777',
        ]);

        $this->assertNotNull($pendaftaran);
        $this->assertSame('pending', $pendaftaran->status);
        $this->assertNotEmpty($pendaftaran->nomor_registrasi);
        $this->assertCount(1, $service->getRiwayat($user));
        $this->assertSame($pendaftaran->id, $service->getDetail($user, $pendaftaran->id)->id);
    }

    public function test_pendaftaran_submit_throws_when_closed(): void
    {
        $user = $this->createUser();
        $service = app(PendaftaranService::class);

        $this->expectException(\App\Exceptions\PendaftaranException::class);
        $this->expectExceptionMessage('Pendaftaran sedang tidak dibuka.');

        $service->submit($user, [
            'nama_anak' => 'Anak',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Kristen',
            'alamat' => 'Alamat',
            'no_hp' => '0812',
        ]);
    }

    public function test_profile_service_updates_info_and_password(): void
    {
        $user = $this->createUser(['password' => 'password123']);
        $service = app(ProfileService::class);

        $this->assertTrue($service->updateInfo($user, [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'no_hp' => '081000000000',
        ]));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);

        $this->assertTrue($service->updatePassword($user, 'passwordbaru'));
        $user->refresh();
        $this->assertTrue(Hash::check('passwordbaru', $user->password));
    }

    public function test_notifikasi_service_mark_read_flow(): void
    {
        $user = $this->createUser();
        $n1 = $this->createNotifikasi($user);
        $this->createNotifikasi($user, ['judul' => 'Kedua']);

        $service = app(NotifikasiService::class);

        $this->assertSame(2, $service->countUnread($user->id));
        $this->assertTrue($service->markAsRead($n1->id, $user->id));
        $this->assertSame(1, $service->countUnread($user->id));

        $service->markAllAsRead($user->id);
        $this->assertSame(0, $service->countUnread($user->id));
        $this->assertSame(2, $service->getAll($user->id)->total());
    }
}
