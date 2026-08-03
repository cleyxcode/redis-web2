<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use App\Services\AdminExportService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiswas extends ListRecords
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->successNotification(null)
                ->action(fn (AdminExportService $export) => $export->siswaExcel(
                    $this->getFilteredTableQuery()
                )),

            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->successNotification(null)
                ->action(fn (AdminExportService $export) => $export->siswaPdf(
                    $this->getFilteredTableQuery()
                )),
        ];
    }
}
