<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Banner;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\InfoPendaftaran;
use App\Models\Notifikasi;
use App\Models\Pendaftaran;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Models\User;

trait CreatesTestData
{
    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    protected function createAdmin(array $overrides = []): User
    {
        return User::factory()->admin()->create($overrides);
    }

    protected function createProfilSekolah(array $overrides = []): ProfilSekolah
    {
        return ProfilSekolah::create(array_merge([
            'nama_sekolah' => 'SD Negeri Warialau',
            'kepala_sekolah' => 'Bapak Kepala',
            'akreditasi' => 'B',
            'tahun_berdiri' => '1980',
            'jumlah_ruang_kelas' => 6,
            'visi' => 'Visi sekolah',
            'misi' => 'Misi sekolah',
            'sejarah' => 'Sejarah sekolah',
            'alamat' => 'Warialau, Maluku',
            'kontak' => '081234567890',
        ], $overrides));
    }

    protected function createGuru(array $overrides = []): Guru
    {
        return Guru::create(array_merge([
            'nama' => 'Guru Test',
            'nip' => '198001012010011001',
            'jabatan' => 'Guru Kelas',
            'mata_pelajaran' => 'Matematika',
            'no_hp' => '081111111111',
            'status' => 'aktif',
        ], $overrides));
    }

    protected function createBerita(User $user, array $overrides = []): Berita
    {
        return Berita::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Berita Test',
            'isi' => 'Isi berita test',
            'kategori' => 'umum',
            'tanggal_publish' => now()->toDateString(),
            'status' => 'publish',
        ], $overrides));
    }

    protected function createGaleri(User $user, array $overrides = []): Galeri
    {
        return Galeri::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Galeri Test',
            'foto' => 'galeri/test.jpg',
            'keterangan' => 'Keterangan galeri',
        ], $overrides));
    }

    protected function createBanner(array $overrides = []): Banner
    {
        return Banner::create(array_merge([
            'judul' => 'Banner Test',
            'gambar' => 'banner/test.jpg',
            'urutan' => 1,
            'status' => 'aktif',
        ], $overrides));
    }

    protected function createInfoPendaftaran(User $user, array $overrides = []): InfoPendaftaran
    {
        return InfoPendaftaran::create(array_merge([
            'user_id' => $user->id,
            'tahun_ajaran' => '2026/2027',
            'tanggal_buka' => now()->subDays(7)->toDateString(),
            'tanggal_tutup' => now()->addDays(30)->toDateString(),
            'kuota' => 50,
            'syarat' => 'Fotokopi KK dan Akta',
            'status' => 'aktif',
        ], $overrides));
    }

    protected function createPendaftaran(User $user, InfoPendaftaran $info, array $overrides = []): Pendaftaran
    {
        return Pendaftaran::create(array_merge([
            'user_id' => $user->id,
            'info_pendaftaran_id' => $info->id,
            'nama_anak' => 'Anak Test',
            'tempat_lahir' => 'Ambon',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Kristen',
            'alamat' => 'Alamat test',
            'no_hp' => '081222333444',
            'status' => 'pending',
        ], $overrides));
    }

    protected function createNotifikasi(User $user, array $overrides = []): Notifikasi
    {
        return Notifikasi::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Notifikasi Test',
            'pesan' => 'Pesan notifikasi',
            'tipe' => 'umum',
            'dibaca' => false,
        ], $overrides));
    }

    protected function createSiswa(array $overrides = []): Siswa
    {
        return Siswa::create(array_merge([
            'nama' => 'Siswa Test',
            'nis' => '2026001',
            'kelas' => '1A',
            'jenis_kelamin' => 'L',
            'tahun_ajaran' => '2026/2027',
            'status' => 'aktif',
        ], $overrides));
    }
}
