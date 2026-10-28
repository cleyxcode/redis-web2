<?php

namespace App\Filament\Resources\LokasiResource\Pages;

use App\Filament\Resources\LokasiResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateLokasi extends CreateRecord
{
    protected static string $resource = LokasiResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('profil_sekolah');
    }
}
