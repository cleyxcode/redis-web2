<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tonggak_sejarah', function (Blueprint $table) {
            $table->id();
            $table->string('tahun', 10);
            $table->text('deskripsi');
            $table->unsignedInteger('urutan')->default(1);
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tonggak_sejarah');
    }
};
