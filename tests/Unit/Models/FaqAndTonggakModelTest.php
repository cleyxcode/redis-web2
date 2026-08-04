<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Faq;
use App\Models\TonggakSejarah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqAndTonggakModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_aktif_and_ordered_scopes(): void
    {
        Faq::create([
            'pertanyaan' => 'B?',
            'jawaban' => 'B',
            'urutan' => 2,
            'status' => 'aktif',
        ]);
        Faq::create([
            'pertanyaan' => 'A?',
            'jawaban' => 'A',
            'urutan' => 1,
            'status' => 'aktif',
        ]);
        Faq::create([
            'pertanyaan' => 'X?',
            'jawaban' => 'X',
            'urutan' => 1,
            'status' => 'nonaktif',
        ]);

        $rows = Faq::query()->aktif()->ordered()->pluck('pertanyaan')->all();

        $this->assertSame(['A?', 'B?'], $rows);
    }

    public function test_tonggak_aktif_and_ordered_scopes(): void
    {
        TonggakSejarah::create([
            'tahun' => '2000',
            'deskripsi' => 'B',
            'urutan' => 2,
            'status' => 'aktif',
        ]);
        TonggakSejarah::create([
            'tahun' => '1990',
            'deskripsi' => 'A',
            'urutan' => 1,
            'status' => 'aktif',
        ]);
        TonggakSejarah::create([
            'tahun' => '2010',
            'deskripsi' => 'Hidden',
            'urutan' => 3,
            'status' => 'nonaktif',
        ]);

        $rows = TonggakSejarah::query()->aktif()->ordered()->get();

        $this->assertCount(2, $rows);
        $this->assertSame('1990', $rows[0]->tahun);
        $this->assertSame('2000', $rows[1]->tahun);
    }
}
