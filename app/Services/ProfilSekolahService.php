<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\TonggakSejarah;
use App\Repositories\Contracts\BannerRepositoryInterface;
use App\Repositories\Contracts\ProfilSekolahRepositoryInterface;
use App\Support\MapEmbed;
use Illuminate\Support\Facades\Cache;

class ProfilSekolahService
{
    public function __construct(
        private ProfilSekolahRepositoryInterface $profilRepo,
        private BannerRepositoryInterface $bannerRepo,
    ) {}

    public function getProfil(): ?array
    {
        return Cache::remember('profil_sekolah', now()->addHours(6), function () {
            $profil = $this->profilRepo->get();
            if (!$profil) {
                return null;
            }

            $settings = Setting::query()
                ->whereIn('key', [
                    'alamat_sekolah',
                    'no_telp',
                    'email_sekolah',
                    'jam_operasional',
                    'latitude',
                    'longitude',
                    'maps_embed',
                    'maps_url',
                    'facebook_url',
                    'instagram_url',
                ])
                ->pluck('value', 'key');

            $lat = MapEmbed::toFloat($settings->get('latitude'));
            $lng = MapEmbed::toFloat($settings->get('longitude'));
            $mapsEmbed = MapEmbed::resolveEmbed($settings->get('maps_embed'), $lat, $lng);
            $mapsUrl = $settings->get('maps_url') ?: MapEmbed::googleMapsUrl($lat, $lng);

            $tonggak = TonggakSejarah::query()
                ->aktif()
                ->ordered()
                ->get(['id', 'tahun', 'deskripsi', 'urutan'])
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'tahun' => $item->tahun,
                    'deskripsi' => $item->deskripsi,
                    'urutan' => $item->urutan,
                ])
                ->values()
                ->all();

            return array_merge($profil->toArray(), [
                'alamat' => $settings->get('alamat_sekolah') ?: $profil->alamat,
                'kontak' => $settings->get('no_telp') ?: $profil->kontak,
                'email_sekolah' => $settings->get('email_sekolah'),
                'jam_operasional' => $settings->get('jam_operasional'),
                'latitude' => $lat !== null ? number_format($lat, 7, '.', '') : null,
                'longitude' => $lng !== null ? number_format($lng, 7, '.', '') : null,
                'maps_embed' => $mapsEmbed,
                'maps_url' => $mapsUrl,
                'facebook_url' => $settings->get('facebook_url'),
                'instagram_url' => $settings->get('instagram_url'),
                'tonggak_sejarah' => $tonggak,
            ]);
        });
    }

    public function getBanner()
    {
        return Cache::remember('banner_aktif', now()->addHours(3), fn () => $this->bannerRepo->allAktif());
    }
}
