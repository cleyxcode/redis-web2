<?php

declare(strict_types=1);

namespace App\Filament\Resources\PendaftaranResource\Pages;

use App\Filament\Resources\PendaftaranResource;
use App\Services\PendaftaranVerificationService;
use App\Support\WhatsApp;
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
        $canVerify = in_array($this->record->status, ['pending', 'perlu_perbaikan'], true);

        return [
            Actions\Action::make('hubungi_wa')
                ->label('Hubungi WA')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(function (): ?string {
                    $msg = 'Halo, kami dari SD Negeri Warialau terkait pendaftaran'
                        .($this->record->nomor_registrasi ? ' No. '.$this->record->nomor_registrasi : '')
                        .' atas nama '.$this->record->nama_anak.'.';

                    return WhatsApp::url($this->record->no_hp, $msg);
                }, shouldOpenInNewTab: true)
                ->visible(fn (): bool => filled(WhatsApp::normalize($this->record->no_hp))),

            Actions\Action::make('terima')
                ->label('Terima')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $canVerify)
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan (opsional)')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->requiresConfirmation()
                ->modalHeading('Terima pendaftaran?')
                ->modalDescription(fn (): string => 'Calon siswa '.$this->record->nama_anak.' akan ditandai diterima dan masuk data siswa. Orang tua mendapat notifikasi otomatis.')
                ->action(function (array $data, PendaftaranVerificationService $service): void {
                    $service->terima($this->record, auth()->user(), $data['catatan'] ?? null);
                    Notification::make()
                        ->title('Pendaftaran diterima · siswa dibuat')
                        ->success()
                        ->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('minta_perbaikan')
                ->label('Minta Perbaikan')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->record->status === 'pending')
                ->form([
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan perbaikan')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Jelaskan dokumen/data yang harus diperbaiki orang tua.'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Minta perbaikan dokumen?')
                ->action(function (array $data, PendaftaranVerificationService $service): void {
                    try {
                        $service->mintaPerbaikan($this->record, auth()->user(), (string) $data['catatan']);
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title('Permintaan perbaikan dikirim')
                        ->success()
                        ->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $canVerify)
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
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'perlu_perbaikan'], true)),
        ];
    }
}
