<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lokasi')->updateOrInsert(
            ['nama' => 'SD Negeri Warialau'],
            [
                'alamat' => 'Jl. Pendidikan No. 1, Warialau, Kec. Aru Utara, Kab. Kepulauan Aru, Maluku 97662',
                'latitude' => -5.7725000,
                'longitude' => 134.5833000,
                'maps_url' => 'https://www.google.com/maps?q=-5.7725000,134.5833000',
                'urutan' => 1,
                'status' => 'aktif',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('lokasi')->where('nama', 'SD Negeri Warialau')->delete();
    }
};
