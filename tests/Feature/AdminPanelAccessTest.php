<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\BeritaResource\Pages\ListBeritas;
use App\Filament\Resources\FaqResource\Pages\ListFaqs;
use App\Filament\Resources\GuruResource\Pages\ListGurus;
use App\Filament\Resources\TonggakSejarahResource\Pages\ListTonggakSejarahs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect();
        $this->get('/admin/login')->assertOk();
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = $this->createUser(['role' => 'orangtua']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_open_dashboard_and_key_resources(): void
    {
        $admin = $this->createAdmin();
        $this->createProfilSekolah();
        $this->createFaq();
        $this->createTonggakSejarah();
        $this->createGuru();
        $this->createBerita($admin);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(ListFaqs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([
                \App\Models\Faq::query()->first(),
            ]);

        Livewire::actingAs($admin)
            ->test(ListTonggakSejarahs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([
                \App\Models\TonggakSejarah::query()->first(),
            ]);

        Livewire::actingAs($admin)
            ->test(ListGurus::class)
            ->assertSuccessful();

        Livewire::actingAs($admin)
            ->test(ListBeritas::class)
            ->assertSuccessful();
    }

    public function test_admin_can_create_faq_via_filament_create_page(): void
    {
        $admin = $this->createAdmin();

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\FaqResource\Pages\CreateFaq::class)
            ->fillForm([
                'pertanyaan' => 'Bagaimana cara mendaftar?',
                'jawaban' => 'Isi formulir di aplikasi atau website.',
                'urutan' => 1,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('faqs', [
            'pertanyaan' => 'Bagaimana cara mendaftar?',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_can_create_tonggak_sejarah_via_filament(): void
    {
        $admin = $this->createAdmin();

        Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\TonggakSejarahResource\Pages\CreateTonggakSejarah::class)
            ->fillForm([
                'tahun' => '2025',
                'deskripsi' => 'Peluncuran aplikasi mobile.',
                'urutan' => 10,
                'status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tonggak_sejarah', [
            'tahun' => '2025',
            'deskripsi' => 'Peluncuran aplikasi mobile.',
        ]);
    }
}
