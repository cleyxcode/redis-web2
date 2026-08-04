<?php

namespace App\Filament\Resources\TonggakSejarahResource\Pages;

use App\Filament\Resources\TonggakSejarahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListTonggakSejarahs extends ListRecords
{
    protected static string $resource = TonggakSejarahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function afterReorder(): void
    {
        Cache::forget('profil_sekolah');
    }
}
