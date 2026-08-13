<?php

declare(strict_types=1);

namespace App\Filament\Resources\PendaftaranResource\Pages;

use App\Filament\Resources\PendaftaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendaftaran extends EditRecord
{
    protected static string $resource = PendaftaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Pastikan verified_at terisi saat status diubah lewat form edit
     * (selain tombol Terima/Tolak di halaman view).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newStatus = $data['status'] ?? null;
        $oldStatus = $this->record->status;

        if (
            is_string($newStatus)
            && $newStatus !== $oldStatus
            && in_array($newStatus, ['diterima', 'ditolak'], true)
        ) {
            $data['verified_at'] = now();
            $data['verified_by'] = auth()->id();
        }

        if ($newStatus === 'pending' || $newStatus === 'perlu_perbaikan') {
            if ($newStatus === 'pending') {
                $data['verified_at'] = null;
                $data['verified_by'] = null;
            }
        }

        return $data;
    }
}
