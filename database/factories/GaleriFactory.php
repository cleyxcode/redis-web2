<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GaleriFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'judul' => fake()->sentence(4),
            'keterangan' => fake()->sentence(10),
            'foto' => 'galeri/placeholder-' . fake()->numerify('###') . '.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
