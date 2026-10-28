<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Berita;
use App\Models\Faq;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\TonggakSejarah;
use Illuminate\Database\Seeder;

final class DemoContentSeeder extends Seeder
{
    private const MINIMUM = 15;

    public function run(): void
    {
        $this->fillFactoryContent();
        $this->fillBanners();
        $this->fillFaqs();
        $this->fillHistory();
    }

    private function fillFactoryContent(): void
    {
        if (Berita::count() < self::MINIMUM) {
            Berita::factory(self::MINIMUM - Berita::count())->create();
        }

        if (Guru::count() < self::MINIMUM) {
            Guru::factory(self::MINIMUM - Guru::count())->create();
        }

        if (Galeri::count() < self::MINIMUM) {
            Galeri::factory(self::MINIMUM - Galeri::count())->create();
        }
    }

    private function fillBanners(): void
    {
        $items = [
            'Kegiatan Literasi dan Membaca Bersama',
            'Upacara Bendera Hari Senin',
            'Pentas Seni dan Budaya Maluku',
            'Lomba Kebersihan Antar Kelas',
            'Program Sekolah Sehat dan Bersih',
            'Pelatihan Komputer untuk Siswa',
            'Kegiatan Pramuka SD Negeri Warialau',
            'Perayaan Hari Pendidikan Nasional',
            'Olimpiade Sains Tingkat Kecamatan',
            'Pertandingan Olahraga Antar Sekolah',
        ];

        $this->fillUntilMinimum(Banner::count(), function (int $index) use ($items): void {
            Banner::create([
                'judul' => $items[$index % count($items)] . ' #' . ($index + 1),
                'gambar' => 'banner/banner-' . (($index % 5) + 1) . '.jpg',
                'urutan' => $index + 1,
                'status' => 'aktif',
            ]);
        });
    }

    private function fillFaqs(): void
    {
        $items = [
            ['Bagaimana cara melihat pengumuman sekolah?', 'Pengumuman sekolah dapat dilihat melalui halaman berita dan notifikasi aplikasi mobile.'],
            ['Apakah orang tua dapat melihat status PPDB?', 'Ya. Orang tua dapat masuk ke aplikasi atau website untuk melihat status pendaftaran secara berkala.'],
            ['Bagaimana cara menghubungi sekolah?', 'Hubungi sekolah melalui nomor kontak pada halaman profil atau datang langsung ke kantor sekolah.'],
            ['Apakah sekolah memiliki kegiatan olahraga?', 'Sekolah memiliki kegiatan olahraga seperti sepak bola, bola voli, atletik, dan senam.'],
            ['Apakah tersedia kegiatan seni?', 'Tersedia kegiatan seni tari, musik, paduan suara, dan pengenalan budaya lokal Maluku.'],
            ['Kapan jadwal pembagian rapor?', 'Jadwal pembagian rapor diumumkan melalui wali kelas dan kanal informasi resmi sekolah.'],
            ['Bagaimana cara melihat data guru?', 'Data guru tersedia pada menu Guru di website dan aplikasi mobile.'],
            ['Apakah sekolah menerima siswa pindahan?', 'Penerimaan siswa pindahan mengikuti ketersediaan kelas dan ketentuan sekolah yang berlaku.'],
            ['Apakah ada perpustakaan sekolah?', 'Sekolah menyediakan perpustakaan dan kegiatan membaca untuk mendukung budaya literasi.'],
            ['Apakah sekolah memiliki laboratorium komputer?', 'Sekolah memiliki fasilitas komputer yang digunakan untuk pembelajaran dan kegiatan digital.'],
        ];

        $this->fillUntilMinimum(Faq::count(), function (int $index) use ($items): void {
            [$question, $answer] = $items[$index % count($items)];
            Faq::create([
                'pertanyaan' => $question . ' #' . ($index + 1),
                'jawaban' => $answer,
                'urutan' => $index + 1,
                'status' => 'aktif',
            ]);
        });
    }

    private function fillHistory(): void
    {
        $items = [
            ['1986', 'Penambahan ruang kelas untuk mendukung pertumbuhan jumlah peserta didik.'],
            ['1989', 'Sekolah mulai mengembangkan kegiatan olahraga dan seni secara terjadwal.'],
            ['1995', 'Perpustakaan sekolah dibangun melalui gotong royong masyarakat.'],
            ['1999', 'Program pembinaan prestasi akademik mulai dilaksanakan secara rutin.'],
            ['2005', 'Sekolah memperoleh bantuan fasilitas belajar dari pemerintah daerah.'],
            ['2008', 'Kegiatan pramuka dan pengembangan karakter menjadi program unggulan.'],
            ['2012', 'Peningkatan fasilitas sanitasi dan lingkungan sekolah yang sehat.'],
            ['2015', 'Pelaksanaan program literasi sekolah untuk seluruh jenjang kelas.'],
            ['2017', 'Sekolah mulai menggunakan perangkat komputer untuk pembelajaran.'],
            ['2020', 'Penerapan pembelajaran berbasis teknologi selama masa pandemi.'],
        ];

        $this->fillUntilMinimum(TonggakSejarah::count(), function (int $index) use ($items): void {
            [$year, $description] = $items[$index % count($items)];
            TonggakSejarah::create([
                'tahun' => $year . '-' . ($index + 1),
                'deskripsi' => $description,
                'urutan' => $index + 1,
                'status' => 'aktif',
            ]);
        });
    }

    private function fillUntilMinimum(int $current, callable $create): void
    {
        for ($index = $current; $index < self::MINIMUM; $index++) {
            $create($index);
        }
    }
}
