<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\PendaftaranException;
use App\Services\PendaftaranService;
use App\Services\PendaftaranVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranVerificationServiceTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_minta_perbaikan_requires_catatan(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pending = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        $this->expectException(InvalidArgumentException::class);

        app(PendaftaranVerificationService::class)
            ->mintaPerbaikan($pending, $admin, '   ');
    }

    public function test_minta_perbaikan_rejects_non_pending_status(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $ditolak = $this->createPendaftaran($parent, $info, [
            'nama_anak' => 'Sudah Tolak',
            'status' => 'ditolak',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya pendaftaran pending yang dapat diminta perbaikan.');

        app(PendaftaranVerificationService::class)
            ->mintaPerbaikan($ditolak, $admin, 'Perbaiki');
    }

    public function test_terima_is_idempotent_for_siswa_creation(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        $service = app(PendaftaranVerificationService::class);
        $service->terima($pendaftaran, $admin, 'OK');
        $service->terima($pendaftaran->fresh(), $admin, 'OK lagi');

        $this->assertSame(1, $pendaftaran->siswa()->count());
    }

    public function test_resubmit_dokumen_only_when_perlu_perbaikan(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, [
            'status' => 'pending',
            'dokumen_kk' => 'pendaftaran/kk/old.jpg',
            'dokumen_akta' => 'pendaftaran/akta/old.jpg',
        ]);

        $service = app(PendaftaranService::class);

        try {
            $service->resubmitDokumen($parent, $pendaftaran->id, [
                'kk' => UploadedFile::fake()->image('kk.jpg'),
            ]);
            $this->fail('Expected PendaftaranException');
        } catch (PendaftaranException $e) {
            $this->assertSame('Dokumen hanya dapat diperbarui jika status perlu perbaikan.', $e->getMessage());
        }

        app(PendaftaranVerificationService::class)
            ->mintaPerbaikan($pendaftaran, $admin, 'Upload ulang KK');

        $updated = $service->resubmitDokumen($parent, $pendaftaran->id, [
            'kk' => UploadedFile::fake()->image('kk-baru.jpg'),
        ]);

        $this->assertSame('pending', $updated->status);
        $this->assertNotSame('pendaftaran/kk/old.jpg', $updated->dokumen_kk);
    }

    public function test_exception_factory_messages(): void
    {
        $this->assertSame(
            'Anda sudah memiliki pendaftaran aktif pada periode ini.',
            PendaftaranException::duplicateUser()->getMessage()
        );
        $this->assertSame(
            'NIK ini sudah terdaftar pada periode pendaftaran aktif.',
            PendaftaranException::duplicateNik()->getMessage()
        );
        $this->assertSame(
            'Data pendaftaran tidak ditemukan.',
            PendaftaranException::notFound()->getMessage()
        );
    }
}
