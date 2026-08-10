<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\Pendaftaran;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class NotifikasiBackfillPendaftaranCommand extends Command
{
    protected $signature = 'notifikasi:backfill-pendaftaran
                            {--dry-run : Hanya hitung, tanpa insert}';

    protected $description = 'Buat notifikasi untuk pendaftaran diterima/ditolak yang belum punya notifikasi';

    public function handle(NotifikasiService $service): int
    {
        $dry = (bool) $this->option('dry-run');
        $created = 0;

        Pendaftaran::query()
            ->whereIn('status', ['diterima', 'ditolak'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($service, $dry, &$created) {
                foreach ($rows as $pendaftaran) {
                    $exists = Notifikasi::query()
                        ->where('user_id', $pendaftaran->user_id)
                        ->where('tipe', 'pendaftaran')
                        ->where('referensi_id', $pendaftaran->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $created++;
                    if ($dry) {
                        continue;
                    }

                    $diterima = $pendaftaran->status === 'diterima';
                    $service->notifyUser(
                        (int) $pendaftaran->user_id,
                        $diterima ? 'Pendaftaran Diterima' : 'Pendaftaran Ditolak',
                        $diterima
                            ? 'Selamat! Pendaftaran atas nama '.$pendaftaran->nama_anak.' telah diterima.'
                            : 'Mohon maaf, pendaftaran atas nama '.$pendaftaran->nama_anak.' tidak dapat diterima.',
                        'pendaftaran',
                        (int) $pendaftaran->id,
                    );
                }
            });

        $this->info(($dry ? '[dry-run] ' : '')."Notifikasi dibuat/akan dibuat: {$created}");

        return self::SUCCESS;
    }
}
