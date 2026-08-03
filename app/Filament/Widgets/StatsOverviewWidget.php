<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\InfoPendaftaranResource;
use App\Filament\Resources\PendaftaranResource;
use App\Models\Aplikasi;
use App\Models\Banner;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\InfoPendaftaran;
use App\Models\Pendaftaran;
use App\Models\Siswa;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $guruAktif = Guru::query()->where('status', 'aktif')->count();
        $siswaAktif = Siswa::query()->where('status', 'aktif')->count();
        $beritaPublish = Berita::query()->where('status', 'publish')->count();
        $galeriCount = Galeri::query()->count();
        $bannerAktif = Banner::query()->where('status', 'aktif')->count();
        $aplikasiAktif = Aplikasi::query()->where('status', 'aktif')->count();

        $pending = Pendaftaran::query()->where('status', 'pending')->count();
        $diterima = Pendaftaran::query()->where('status', 'diterima')->count();
        $ditolak = Pendaftaran::query()->where('status', 'ditolak')->count();
        $totalPendaftaran = $pending + $diterima + $ditolak;

        $infoAktif = InfoPendaftaran::query()->where('status', 'aktif')->first();
        $ppdbLabel = $infoAktif
            ? 'Tahun '.$infoAktif->tahun_ajaran
            : 'Tidak aktif';

        return [
            Stat::make('Guru Aktif', $guruAktif)
                ->description('Tenaga pendidik aktif')
                ->icon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('Siswa Aktif', $siswaAktif)
                ->description('Peserta didik aktif')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Berita Tayang', $beritaPublish)
                ->description('Status publish')
                ->icon('heroicon-o-newspaper')
                ->color('primary'),

            Stat::make('Galeri', $galeriCount)
                ->description('Total foto galeri')
                ->icon('heroicon-o-photo')
                ->color('gray'),

            Stat::make('Banner Aktif', $bannerAktif)
                ->description('Ditampilkan di beranda')
                ->icon('heroicon-o-rectangle-stack')
                ->color('warning'),

            Stat::make('Aplikasi Android', $aplikasiAktif)
                ->description('Versi aktif tersedia')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('danger'),

            Stat::make('Pendaftaran Pending', $pending)
                ->description('Klik untuk membuka daftar pending')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(PendaftaranResource::getUrl('index'))
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Pendaftaran Diterima', $diterima)
                ->description('Dari total '.$totalPendaftaran.' pendaftar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(PendaftaranResource::getUrl('index')),

            Stat::make('Pendaftaran Ditolak', $ditolak)
                ->description('Tidak lolos seleksi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->url(PendaftaranResource::getUrl('index')),

            Stat::make('Status PPDB', $ppdbLabel)
                ->description($infoAktif
                    ? 'Kuota '.$infoAktif->kuota.' siswa'
                    : 'Belum ada info pendaftaran aktif')
                ->icon('heroicon-o-clipboard-document-list')
                ->color($infoAktif ? 'success' : 'gray')
                ->url(InfoPendaftaranResource::getUrl('index')),
        ];
    }
}
