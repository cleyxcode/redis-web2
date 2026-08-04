<?php

namespace App\Filament\Resources\ProfilSekolahResource\Pages;

use App\Filament\Resources\ProfilSekolahResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateProfilSekolah extends CreateRecord
{
    protected static string $resource = ProfilSekolahResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('profil_sekolah');
    }
}
