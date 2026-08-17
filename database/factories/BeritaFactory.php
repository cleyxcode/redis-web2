<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BeritaFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['Kegiatan', 'Akademik', 'Ekstrakurikuler', 'Prestasi', 'Berita', 'Pengumuman'];
        
        return [
            'user_id' => 1,
            'judul' => fake()->sentence(6),
            'kategori' => fake()->randomElement($categories),
            'isi' => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'status' => 'publish',
            'tanggal_publish' => fake()->dateTimeBetween('-6 months', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
