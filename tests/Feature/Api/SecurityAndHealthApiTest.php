<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class SecurityAndHealthApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_health_endpoint_returns_status(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'checks' => ['app', 'database', 'cache', 'queue'],
                'timestamp',
            ]);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'nobody@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_rejects_fake_mime_document(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createInfoPendaftaran($admin);
        Sanctum::actingAs($user);

        // Client claims PDF but content is plain text
        $fakePdf = UploadedFile::fake()->createWithContent(
            'kk.pdf',
            'this is not a real pdf'
        );

        $this->post('/api/v1/pendaftaran', [
            'nama_anak' => 'Anak',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Kristen',
            'alamat' => 'Alamat',
            'no_hp' => '0812',
            'dokumen_kk' => $fakePdf,
            'dokumen_akta' => UploadedFile::fake()->image('akta.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dokumen_kk']);
    }
}
