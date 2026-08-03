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
            $table->string('dokumen_kk')->nullable()->after('dokumen');
            $table->string('dokumen_akta')->nullable()->after('dokumen_kk');
            $table->string('dokumen_pas_foto')->nullable()->after('dokumen_akta');
            $table->text('catatan_verifikasi')->nullable()->after('status');
            $table->timestamp('verified_at')->nullable()->after('catatan_verifikasi');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'dokumen_kk',
                'dokumen_akta',
                'dokumen_pas_foto',
                'catatan_verifikasi',
                'verified_at',
                'deleted_at',
            ]);
        });
    }
};
