<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/pendaftaran', [])->assertUnauthorized();
    }

    public function test_store_fails_when_registration_closed(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/pendaftaran', [
            'nama_anak' => 'Anak',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Kristen',
            'alamat' => 'Alamat',
            'no_hp' => '0812',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Pendaftaran sedang tidak dibuka.');
    }

    public function test_store_riwayat_and_show(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createInfoPendaftaran($admin);
        Sanctum::actingAs($user);

        $store = $this->post('/api/v1/pendaftaran', [
            'nama_anak' => 'Anak Warialau',
            'tempat_lahir' => 'Ambon',
            'tanggal_lahir' => '2019-02-02',
            'jenis_kelamin' => 'P',
            'agama' => 'Kristen',
            'alamat' => 'Jl. Pendidikan',
            'no_hp' => '081234567890',
            'dokumen_kk' => UploadedFile::fake()->image('kk.jpg'),
            'dokumen_akta' => UploadedFile::fake()->image('akta.jpg'),
        ], ['Accept' => 'application/json']);

        $store->assertCreated()
            ->assertJsonPath('message', 'Pendaftaran berhasil diajukan.')
            ->assertJsonPath('pendaftaran.nama_anak', 'Anak Warialau')
            ->assertJsonPath('pendaftaran.status', 'pending')
            ->assertJsonPath('pendaftaran.dokumen_lengkap', true);

        $id = $store->json('pendaftaran.id');

        $riwayat = $this->getJson('/api/v1/pendaftaran/riwayat')->assertOk();
        $this->assertCount(1, $riwayat->json());

        $this->getJson('/api/v1/pendaftaran/'.$id)
            ->assertOk()
            ->assertJsonPath('nama_anak', 'Anak Warialau');

        $this->getJson('/api/v1/pendaftaran/99999')
            ->assertNotFound();
    }

    public function test_store_requires_documents(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createInfoPendaftaran($admin);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/pendaftaran', [
            'nama_anak' => 'Anak',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Kristen',
            'alamat' => 'Alamat',
            'no_hp' => '0812',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['dokumen_kk', 'dokumen_akta']);
    }

    public function test_user_cannot_view_other_user_pendaftaran(): void
    {
        $admin = $this->createAdmin();
        $owner = $this->createUser(['email' => 'owner@example.com']);
        $other = $this->createUser(['email' => 'other@example.com']);
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($owner, $info);

        Sanctum::actingAs($other);

        $this->getJson('/api/v1/pendaftaran/'.$pendaftaran->id)
            ->assertNotFound();
    }
}
