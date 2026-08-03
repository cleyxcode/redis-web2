<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\InfoPendaftaran;
use Illuminate\Support\Facades\Cache;

class InfoPendaftaranObserver
{
    public function saved(InfoPendaftaran $info): void
    {
        if ($info->status === 'aktif') {
            InfoPendaftaran::query()
                ->where('id', '!=', $info->id)
                ->where('status', 'aktif')
                ->update(['status' => 'nonaktif']);
        }

        Cache::forget('info_pendaftaran_aktif');
    }

    public function deleted(InfoPendaftaran $info): void
    {
        Cache::forget('info_pendaftaran_aktif');
    }
}
