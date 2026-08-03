<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('created_at')->label('Waktu')->dateTime(),
            Infolists\Components\TextEntry::make('causer.name')->label('Oleh')->placeholder('Sistem'),
            Infolists\Components\TextEntry::make('description')->label('Deskripsi'),
            Infolists\Components\TextEntry::make('event')->badge()->placeholder('—'),
            Infolists\Components\TextEntry::make('subject_type')->label('Model')
                ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
            Infolists\Components\TextEntry::make('subject_id')->label('ID subjek'),
            Infolists\Components\TextEntry::make('properties')
                ->label('Detail')
                ->formatStateUsing(fn ($state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '—')
                ->columnSpanFull(),
        ]);
    }
}
