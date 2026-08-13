<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Services\PendaftaranVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranVerificationAndDeviceTokenTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_admin_verification_creates_notification_and_activity_log(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        $service = app(PendaftaranVerificationService::class);
        $service->terima($pendaftaran, $admin, 'Selamat bergabung');

        $pendaftaran->refresh();
        $this->assertSame('diterima', $pendaftaran->status);
        $this->assertSame('Selamat bergabung', $pendaftaran->catatan_verifikasi);
        $this->assertSame($admin->id, $pendaftaran->verified_by);
        $this->assertNotNull($pendaftaran->verified_at);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $parent->id,
            'tipe' => 'pendaftaran',
            'referensi_id' => $pendaftaran->id,
            'judul' => 'Pendaftaran Diterima',
        ]);

        $this->assertDatabaseHas('siswa', [
            'pendaftaran_id' => $pendaftaran->id,
            'nama' => $pendaftaran->nama_anak,
        ]);

        $notif = Notifikasi::query()->where('user_id', $parent->id)->latest('id')->first();
        $this->assertStringContainsString('Selamat bergabung', (string) $notif?->pesan);

        $this->assertTrue(
            Activity::query()
                ->where('subject_type', $pendaftaran::class)
                ->where('subject_id', $pendaftaran->id)
                ->exists()
        );
    }

    public function test_tolak_requires_alasan(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info);

        $this->expectException(\InvalidArgumentException::class);

        app(PendaftaranVerificationService::class)->tolak($pendaftaran, $admin, '  ');
    }

    public function test_device_token_register_and_unregister(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-abc',
            'platform' => 'android',
            'device_name' => 'Pixel',
        ])->assertOk()
            ->assertJsonPath('message', 'Device token tersimpan.');

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-abc',
            'platform' => 'android',
        ]);

        $this->deleteJson('/api/v1/device-tokens', [
            'token' => 'fcm-token-abc',
        ])->assertOk();

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'fcm-token-abc',
        ]);
    }

    public function test_pendaftaran_soft_delete(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info);

        $pendaftaran->delete();

        $this->assertSoftDeleted('pendaftaran', ['id' => $pendaftaran->id]);
    }
}
