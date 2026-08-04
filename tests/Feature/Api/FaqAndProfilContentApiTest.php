<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class FaqAndProfilContentApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_faq_endpoint_returns_active_ordered_only(): void
    {
        $this->createFaq([
            'pertanyaan' => 'FAQ kedua?',
            'jawaban' => 'Jawaban 2',
            'urutan' => 2,
        ]);
        $this->createFaq([
            'pertanyaan' => 'FAQ pertama?',
            'jawaban' => 'Jawaban 1',
            'urutan' => 1,
        ]);
        $this->createFaq([
            'pertanyaan' => 'FAQ nonaktif?',
            'jawaban' => 'Tidak tampil',
            'urutan' => 3,
            'status' => 'nonaktif',
        ]);

        $response = $this->getJson('/api/v1/faq')->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertSame('FAQ pertama?', $response->json('data.0.pertanyaan'));
        $this->assertSame('FAQ kedua?', $response->json('data.1.pertanyaan'));
        $this->assertSame('Jawaban 1', $response->json('data.0.jawaban'));
    }

    public function test_profil_sekolah_includes_tujuan_tonggak_and_jam_operasional(): void
    {
        Cache::flush();

        $this->createProfilSekolah([
            'tujuan' => 'Mencetak generasi berkarakter',
            'visi' => 'Visi uji',
            'misi' => "1. Poin A\n2. Poin B",
        ]);
        $this->createTonggakSejarah([
            'tahun' => '2010',
            'deskripsi' => 'Renovasi gedung',
            'urutan' => 2,
        ]);
        $this->createTonggakSejarah([
            'tahun' => '1985',
            'deskripsi' => 'Pendirian',
            'urutan' => 1,
        ]);
        $this->createTonggakSejarah([
            'tahun' => '1999',
            'deskripsi' => 'Hidden',
            'status' => 'nonaktif',
            'urutan' => 3,
        ]);

        Setting::updateOrCreate(
            ['key' => 'jam_operasional'],
            ['value' => "Senin – Sabtu\n07.30 – 14.00 WIT", 'type' => 'text']
        );

        $response = $this->getJson('/api/v1/profil-sekolah')->assertOk();

        $response
            ->assertJsonPath('tujuan', 'Mencetak generasi berkarakter')
            ->assertJsonPath('visi', 'Visi uji')
            ->assertJsonPath('jam_operasional', "Senin – Sabtu\n07.30 – 14.00 WIT");

        $this->assertCount(2, $response->json('tonggak_sejarah'));
        $this->assertSame('1985', $response->json('tonggak_sejarah.0.tahun'));
        $this->assertSame('Pendirian', $response->json('tonggak_sejarah.0.deskripsi'));
        $this->assertSame('2010', $response->json('tonggak_sejarah.1.tahun'));
    }

    public function test_faq_cache_is_cleared_conceptually_by_fresh_query_after_forget(): void
    {
        $this->createFaq(['pertanyaan' => 'Cached FAQ?', 'jawaban' => 'Yes']);

        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(1, 'data');

        Cache::forget('faqs_aktif');
        $this->createFaq([
            'pertanyaan' => 'FAQ baru?',
            'jawaban' => 'Baru',
            'urutan' => 2,
        ]);

        // Cache key forgotten; next request rebuilds with 2 items
        $this->getJson('/api/v1/faq')->assertOk()->assertJsonCount(2, 'data');
    }
}
