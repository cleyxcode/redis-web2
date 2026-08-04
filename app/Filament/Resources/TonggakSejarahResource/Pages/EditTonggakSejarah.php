<?php

namespace App\Filament\Resources\TonggakSejarahResource\Pages;

use App\Filament\Resources\TonggakSejarahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditTonggakSejarah extends EditRecord
{
    protected static string $resource = TonggakSejarahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => Cache::forget('profil_sekolah')),
        ];
    }

    protected function afterSave(): void
    {
        Cache::forget('profil_sekolah');
    }
}
