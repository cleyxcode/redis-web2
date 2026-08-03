<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Pendaftaran;
use Filament\Widgets\ChartWidget;

class PendaftaranStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Pendaftaran PPDB';

    protected static ?string $description = 'Perbandingan pending, diterima, dan ditolak';

    protected static ?string $maxHeight = '280px';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $pending = Pendaftaran::query()->where('status', 'pending')->count();
        $diterima = Pendaftaran::query()->where('status', 'diterima')->count();
        $ditolak = Pendaftaran::query()->where('status', 'ditolak')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Pendaftaran',
                    'data' => [$pending, $diterima, $ditolak],
                    'backgroundColor' => [
                        'rgb(245, 158, 11)',
                        'rgb(16, 185, 129)',
                        'rgb(244, 63, 94)',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Pending', 'Diterima', 'Ditolak'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
        ];
    }
}
