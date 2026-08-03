<?php

declare(strict_types=1);

namespace App\Filament\Resources\PendaftaranResource\Pages;

use App\Filament\Resources\PendaftaranResource;
use App\Services\AdminExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendaftarans extends ListRecords
{
    protected static string $resource = PendaftaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->successNotification(null)
                ->action(fn (AdminExportService $export) => $export->pendaftaranExcel(
                    $this->getFilteredTableQuery()
                )),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->successNotification(null)
                ->action(fn (AdminExportService $export) => $export->pendaftaranPdf(
                    $this->getFilteredTableQuery()
                )),
        ];
    }
}
