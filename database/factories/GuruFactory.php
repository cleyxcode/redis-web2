<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GuruFactory extends Factory
{
    public function definition(): array
    {
        $jabatan = ['Guru Kelas', 'Guru Bidang Studi', 'Guru PJOK', 'Guru TIK', 'Guru BK', 'Guru Agama'];
        $mataPelajaran = ['Tematik', 'Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Penjaskes', 'TIK', 'Seni Budaya', null];
        
        return [
            'nama' => fake()->name(),
            'nip' => fake()->numerify('##############'),
            'jabatan' => fake()->randomElement($jabatan),
            'mata_pelajaran' => fake()->randomElement($mataPelajaran),
            'no_hp' => fake()->numerify('08##########'),
            'status' => 'aktif',
            'foto' => 'guru/placeholder-' . fake()->numerify('###') . '.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
