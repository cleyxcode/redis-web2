<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PendaftaranStatusMail;
use App\Models\Siswa;
use App\Services\PendaftaranVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class PendaftaranWorkflowTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_store_assigns_nomor_and_blocks_duplicate_user(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createInfoPendaftaran($admin, ['tahun_ajaran' => '2026/2027']);
        Sanctum::actingAs($user);

        $first = $this->post('/api/v1/pendaftaran', $this->payload(), ['Accept' => 'application/json']);
        $first->assertCreated()
            ->assertJsonPath('pendaftaran.status', 'pending');
        $this->assertNotEmpty($first->json('pendaftaran.nomor_registrasi'));
        $this->assertStringStartsWith('PPDB-20262027-', (string) $first->json('pendaftaran.nomor_registrasi'));

        $this->post('/api/v1/pendaftaran', $this->payload(['nama_anak' => 'Anak Lain']), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Anda sudah memiliki pendaftaran aktif pada periode ini.');
    }

    public function test_duplicate_nik_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $userA = $this->createUser(['email' => 'a@example.com']);
        $userB = $this->createUser(['email' => 'b@example.com']);
        $this->createInfoPendaftaran($admin);

        Sanctum::actingAs($userA);
        $this->post('/api/v1/pendaftaran', $this->payload(['nik' => '1234567890123456']), ['Accept' => 'application/json'])
            ->assertCreated();

        Sanctum::actingAs($userB);
        $this->post('/api/v1/pendaftaran', $this->payload([
            'nama_anak' => 'Anak B',
            'nik' => '1234567890123456',
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'NIK ini sudah terdaftar pada periode pendaftaran aktif.');
    }

    public function test_minta_perbaikan_resubmit_and_accept_creates_siswa(): void
    {
        Storage::fake('public');
        Mail::fake();

        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, [
            'status' => 'pending',
            'nomor_registrasi' => 'PPDB-TEST-0001',
            'dokumen_kk' => 'pendaftaran/kk/old.jpg',
            'dokumen_akta' => 'pendaftaran/akta/old.jpg',
        ]);

        $service = app(PendaftaranVerificationService::class);
        $service->mintaPerbaikan($pendaftaran, $admin, 'KK kurang jelas');

        $pendaftaran->refresh();
        $this->assertSame('perlu_perbaikan', $pendaftaran->status);
        Mail::assertQueued(PendaftaranStatusMail::class);

        Sanctum::actingAs($parent);
        $this->post('/api/v1/pendaftaran/'.$pendaftaran->id.'/dokumen', [
            'dokumen_kk' => UploadedFile::fake()->image('kk-baru.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('pendaftaran.status', 'pending');

        $service->terima($pendaftaran->fresh(), $admin, 'Selamat');

        $pendaftaran->refresh();
        $this->assertSame('diterima', $pendaftaran->status);
        $this->assertDatabaseHas('siswa', [
            'pendaftaran_id' => $pendaftaran->id,
            'nama' => $pendaftaran->nama_anak,
        ]);
        $this->assertSame(1, Siswa::query()->where('pendaftaran_id', $pendaftaran->id)->count());
    }

    public function test_bukti_pdf_download(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, [
            'nomor_registrasi' => 'PPDB-PDF-0001',
        ]);

        Sanctum::actingAs($parent);
        $this->get('/api/v1/pendaftaran/'.$pendaftaran->id.'/bukti-pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($parent)
            ->get(route('pendaftaran.bukti-pdf', $pendaftaran->id))
            ->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_anak' => 'Anak Warialau',
            'tempat_lahir' => 'Ambon',
            'tanggal_lahir' => '2019-02-02',
            'jenis_kelamin' => 'P',
            'agama' => 'Kristen',
            'alamat' => 'Jl. Pendidikan',
            'no_hp' => '081234567890',
            'dokumen_kk' => UploadedFile::fake()->image('kk.jpg'),
            'dokumen_akta' => UploadedFile::fake()->image('akta.jpg'),
        ], $overrides);
    }
}
