<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\PendaftaranResource;
use App\Models\Pendaftaran;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPendaftaranWidget extends BaseWidget
{
    protected static ?string $heading = 'Pendaftaran Terbaru';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(Pendaftaran::query())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nama_anak')
                    ->label('Nama Anak')
                    ->limit(24)
                    ->searchable(false),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'diterima' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y'),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Pendaftaran $record): string => PendaftaranResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([8])
            ->defaultPaginationPageOption(8)
            ->striped();
    }
}
