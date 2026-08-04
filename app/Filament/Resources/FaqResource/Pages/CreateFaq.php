<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('faqs_aktif');
    }
}
