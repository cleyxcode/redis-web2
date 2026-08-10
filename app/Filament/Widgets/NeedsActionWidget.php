<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\BeritaResource;
use App\Filament\Resources\PendaftaranResource;
use App\Models\Berita;
use App\Models\Pendaftaran;
use App\Services\FcmPushService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Throwable;

class NeedsActionWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $pending = Pendaftaran::query()->where('status', 'pending')->count();
        $draftBerita = Berita::query()->where('status', 'draft')->count();

        $failedJobs = 0;
        $pendingJobs = 0;
        try {
            $failedJobs = (int) DB::table('failed_jobs')->count();
            $pendingJobs = (int) DB::table('jobs')->count();
        } catch (Throwable) {
            //
        }

        $fcmOk = app(FcmPushService::class)->isConfigured();

        return [
            Stat::make('Perlu Verifikasi', $pending)
                ->description($pending > 0 ? 'Pendaftaran menunggu tindakan' : 'Tidak ada antrean')
                ->icon('heroicon-o-clipboard-document-check')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(PendaftaranResource::getUrl('index')),

            Stat::make('Berita Draft', $draftBerita)
                ->description('Belum dipublish')
                ->icon('heroicon-o-pencil-square')
                ->color($draftBerita > 0 ? 'gray' : 'success')
                ->url(BeritaResource::getUrl('index')),

            Stat::make('Antrian Job', $pendingJobs)
                ->description($failedJobs > 0 ? "{$failedJobs} gagal — cek queue" : 'Push/email menunggu worker')
                ->icon('heroicon-o-queue-list')
                ->color($failedJobs > 0 ? 'danger' : ($pendingJobs > 0 ? 'warning' : 'success')),

            Stat::make('Push FCM', $fcmOk ? 'Siap' : 'Belum set')
                ->description($fcmOk ? 'Credentials terdeteksi' : 'Isi FCM_PROJECT_ID & FCM_CREDENTIALS')
                ->icon('heroicon-o-bell-alert')
                ->color($fcmOk ? 'success' : 'danger'),
        ];
    }
}
