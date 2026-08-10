<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\PendaftaranResource;
use App\Models\Pendaftaran;
use Filament\Widgets\Widget;

class PendingPendaftaranAlertWidget extends Widget
{
    protected static string $view = 'filament.widgets.pending-pendaftaran-alert';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Pendaftaran::query()->where('status', 'pending')->exists();
    }

    public function getPendingCount(): int
    {
        return Pendaftaran::query()->where('status', 'pending')->count();
    }

    public function getPendaftaranIndexUrl(): string
    {
        return PendaftaranResource::getUrl('index');
    }
}
