<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->string('nomor_registrasi', 40)->nullable()->unique()->after('info_pendaftaran_id');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->foreignId('pendaftaran_id')->nullable()->after('id')
                ->constrained('pendaftaran')->nullOnDelete();
            $table->unique('pendaftaran_id');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pendaftaran_id');
        });

        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->dropUnique(['nomor_registrasi']);
            $table->dropColumn('nomor_registrasi');
        });
    }
};
