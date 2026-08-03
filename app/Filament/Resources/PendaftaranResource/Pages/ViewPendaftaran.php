<?php

declare(strict_types=1);

namespace App\Filament\Resources\PendaftaranResource\Pages;

use App\Filament\Resources\PendaftaranResource;
use App\Services\PendaftaranVerificationService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;

class ViewPendaftaran extends ViewRecord
{
    protected static string $resource = PendaftaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('terima')
                ->label('Terima')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan (opsional)')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->requiresConfirmation()
                ->modalHeading('Terima pendaftaran?')
                ->modalDescription(fn (): string => 'Calon siswa '.$this->record->nama_anak.' akan ditandai diterima. Orang tua mendapat notifikasi otomatis.')
                ->action(function (array $data, PendaftaranVerificationService $service): void {
                    $service->terima($this->record, auth()->user(), $data['catatan'] ?? null);
                    Notification::make()
                        ->title('Pendaftaran diterima')
                        ->success()
                        ->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->form([
                    Forms\Components\Textarea::make('alasan')
                        ->label('Alasan penolakan')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->requiresConfirmation()
                ->modalHeading('Tolak pendaftaran?')
                ->modalDescription(fn (): string => 'Pendaftaran '.$this->record->nama_anak.' akan ditolak. Alasan dikirim ke orang tua.')
                ->action(function (array $data, PendaftaranVerificationService $service): void {
                    try {
                        $service->tolak($this->record, auth()->user(), (string) $data['alasan']);
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title('Pendaftaran ditolak')
                        ->success()
                        ->send();
                    $this->record->refresh();
                }),

            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->status === 'pending'),
        ];
    }
}
