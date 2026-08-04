<?php

namespace App\Filament\Resources\TonggakSejarahResource\Pages;

use App\Filament\Resources\TonggakSejarahResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateTonggakSejarah extends CreateRecord
{
    protected static string $resource = TonggakSejarahResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('profil_sekolah');
    }
}
