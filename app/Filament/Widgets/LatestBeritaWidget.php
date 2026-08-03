<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\BeritaResource;
use App\Models\Berita;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBeritaWidget extends BaseWidget
{
    protected static ?string $heading = 'Berita Terbaru';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Berita::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->limit(50)
                    ->searchable(false),
                Tables\Columns\TextColumn::make('kategori')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'publish' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tanggal_publish')
                    ->label('Publish')
                    ->date('d M Y')
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Berita $record): string => BeritaResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->striped();
    }
}
