<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranModelTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_dokumen_lengkap_and_public_urls(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);

        $incomplete = $this->createPendaftaran($user, $info, [
            'dokumen_kk' => 'pendaftaran/kk/a.jpg',
            'dokumen_akta' => null,
        ]);
        $this->assertFalse($incomplete->dokumen_lengkap);
        $this->assertStringContainsString('storage/pendaftaran/kk/a.jpg', (string) $incomplete->dokumen_kk_url);
        $this->assertNull($incomplete->dokumen_akta_url);

        $complete = $this->createPendaftaran($user, $info, [
            'nama_anak' => 'Lengkap',
            'dokumen_kk' => 'pendaftaran/kk/b.jpg',
            'dokumen_akta' => 'pendaftaran/akta/b.jpg',
            'dokumen_pas_foto' => 'pendaftaran/pas_foto/b.jpg',
            'dokumen' => 'pendaftaran/kk/b.jpg',
            'status' => 'ditolak',
        ]);
        $this->assertTrue($complete->dokumen_lengkap);
        $this->assertNotNull($complete->dokumen_akta_url);
        $this->assertNotNull($complete->dokumen_pas_foto_url);
        $this->assertNotNull($complete->dokumen_url);
    }

    public function test_relations_user_info_and_siswa(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($user, $info, [
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'status' => 'diterima',
        ]);
        $siswa = $this->createSiswa([
            'pendaftaran_id' => $pendaftaran->id,
            'nama' => $pendaftaran->nama_anak,
        ]);

        $this->assertSame($user->id, $pendaftaran->user->id);
        $this->assertSame($info->id, $pendaftaran->infoPendaftaran->id);
        $this->assertSame($admin->id, $pendaftaran->verifier->id);
        $this->assertSame($siswa->id, $pendaftaran->siswa->id);
    }
}
