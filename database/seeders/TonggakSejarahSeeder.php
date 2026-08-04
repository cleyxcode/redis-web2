<?php

namespace Database\Seeders;

use App\Models\TonggakSejarah;
use Illuminate\Database\Seeder;

class TonggakSejarahSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['tahun' => '1985', 'deskripsi' => 'Pendirian SD Negeri Warialau dengan 3 ruang kelas pertama.', 'urutan' => 1],
            ['tahun' => '1993', 'deskripsi' => 'Pembangunan gedung tambahan dan penambahan tenaga pengajar.', 'urutan' => 2],
            ['tahun' => '2002', 'deskripsi' => 'Peraihan akreditasi B dari BAN-S/M untuk pertama kalinya.', 'urutan' => 3],
            ['tahun' => '2010', 'deskripsi' => 'Renovasi total gedung sekolah dengan dukungan APBD daerah.', 'urutan' => 4],
            ['tahun' => '2018', 'deskripsi' => 'Peraihan akreditasi A, tertinggi dalam sejarah sekolah.', 'urutan' => 5],
            ['tahun' => '2024', 'deskripsi' => 'Pengembangan laboratorium komputer berbasis teknologi terkini.', 'urutan' => 6],
        ];

        foreach ($items as $item) {
            TonggakSejarah::updateOrCreate(
                ['tahun' => $item['tahun'], 'deskripsi' => $item['deskripsi']],
                array_merge($item, ['status' => 'aktif'])
            );
        }
    }
}
