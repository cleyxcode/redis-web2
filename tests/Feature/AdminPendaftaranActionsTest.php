<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PendaftaranResource\Pages\EditPendaftaran;
use App\Filament\Resources\PendaftaranResource\Pages\ViewPendaftaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminPendaftaranActionsTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_admin_can_terima_pendaftaran_from_view_page(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, [
            'status' => 'pending',
            'nama_anak' => 'Calon Diterima',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewPendaftaran::class, ['record' => $pendaftaran->getRouteKey()])
            ->callAction('terima', data: [
                'catatan' => 'Dokumen lengkap',
            ]);

        $pendaftaran->refresh();
        $this->assertSame('diterima', $pendaftaran->status);
        $this->assertSame('Dokumen lengkap', $pendaftaran->catatan_verifikasi);
        $this->assertSame($admin->id, $pendaftaran->verified_by);
        $this->assertDatabaseHas('siswa', [
            'pendaftaran_id' => $pendaftaran->id,
            'nama' => 'Calon Diterima',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $parent->id,
            'tipe' => 'pendaftaran',
            'judul' => 'Pendaftaran Diterima',
        ]);
    }

    public function test_admin_can_tolak_pendaftaran_from_view_page(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(ViewPendaftaran::class, ['record' => $pendaftaran->getRouteKey()])
            ->callAction('tolak', data: [
                'alasan' => 'Usia belum mencukupi',
            ]);

        $pendaftaran->refresh();
        $this->assertSame('ditolak', $pendaftaran->status);
        $this->assertSame('Usia belum mencukupi', $pendaftaran->catatan_verifikasi);
        $this->assertSame(0, Siswa::query()->where('pendaftaran_id', $pendaftaran->id)->count());
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $parent->id,
            'judul' => 'Pendaftaran Ditolak',
        ]);
    }

    public function test_admin_can_minta_perbaikan_from_view_page(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(ViewPendaftaran::class, ['record' => $pendaftaran->getRouteKey()])
            ->callAction('minta_perbaikan', data: [
                'catatan' => 'Scan KK buram',
            ]);

        $pendaftaran->refresh();
        $this->assertSame('perlu_perbaikan', $pendaftaran->status);
        $this->assertSame('Scan KK buram', $pendaftaran->catatan_verifikasi);
        $this->assertNull($pendaftaran->verified_at);
    }

    public function test_edit_page_sets_verified_fields_when_status_changed(): void
    {
        $admin = $this->createAdmin();
        $parent = $this->createUser();
        $info = $this->createInfoPendaftaran($admin);
        $pendaftaran = $this->createPendaftaran($parent, $info, ['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(EditPendaftaran::class, ['record' => $pendaftaran->getRouteKey()])
            ->fillForm([
                'status' => 'ditolak',
                'catatan_verifikasi' => 'Diedit dari form',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $pendaftaran->refresh();
        $this->assertSame('ditolak', $pendaftaran->status);
        $this->assertNotNull($pendaftaran->verified_at);
        $this->assertSame($admin->id, $pendaftaran->verified_by);
    }
}
