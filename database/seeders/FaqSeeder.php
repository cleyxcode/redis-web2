<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'pertanyaan' => 'Kapan pendaftaran siswa baru dibuka?',
                'jawaban' => 'Pendaftaran siswa baru dibuka setiap tahun sekitar bulan Februari–April. Pantau pengumuman resmi di website ini atau kunjungi kantor sekolah untuk informasi terkini.',
                'urutan' => 1,
            ],
            [
                'pertanyaan' => 'Apa saja dokumen pendaftaran yang diperlukan?',
                'jawaban' => 'Dokumen yang diperlukan: (1) Fotokopi Akta Kelahiran, (2) Fotokopi Kartu Keluarga, (3) Pas foto 3×4 sebanyak 3 lembar, dan (4) Formulir pendaftaran yang diisi lengkap.',
                'urutan' => 2,
            ],
            [
                'pertanyaan' => 'Berapa kuota penerimaan siswa baru?',
                'jawaban' => 'Kami menerima sekitar 120 siswa baru per tahun yang dibagi dalam beberapa rombongan belajar. Penerimaan dilakukan secara transparan berdasarkan usia dan ketersediaan tempat.',
                'urutan' => 3,
            ],
            [
                'pertanyaan' => 'Apakah ada biaya pendaftaran?',
                'jawaban' => 'Tidak ada biaya pendaftaran. Seluruh proses penerimaan siswa baru di SD Negeri Warialau dilaksanakan secara gratis sebagai sekolah negeri.',
                'urutan' => 4,
            ],
            [
                'pertanyaan' => 'Apa saja ekstrakurikuler yang tersedia?',
                'jawaban' => 'Kami menyediakan: Pramuka, Olahraga (bulu tangkis, futsal), Kesenian (tari, paduan suara), Literasi, Sains Club, Dokter Kecil, dan TIK.',
                'urutan' => 5,
            ],
        ];

        foreach ($items as $item) {
            Faq::updateOrCreate(
                ['pertanyaan' => $item['pertanyaan']],
                array_merge($item, ['status' => 'aktif'])
            );
        }
    }
}
