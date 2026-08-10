<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Notifikasi;
use App\Models\ProfilSekolah;
use App\Models\Setting;
use App\Support\MapEmbed;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $profil = ProfilSekolah::query()->first();
        $settings = Setting::query()->pluck('value', 'key');

        $lat = MapEmbed::toFloat($settings->get('latitude'));
        $lng = MapEmbed::toFloat($settings->get('longitude'));
        $mapsEmbed = MapEmbed::resolveEmbed($settings->get('maps_embed'), $lat, $lng);

        if ($mapsEmbed) {
            $settings['maps_embed'] = $mapsEmbed;
        }
        if ($lat !== null && $lng !== null) {
            $settings['latitude'] = number_format($lat, 7, '.', '');
            $settings['longitude'] = number_format($lng, 7, '.', '');
            $settings['maps_url'] = $settings->get('maps_url') ?: MapEmbed::googleMapsUrl($lat, $lng);
        }

        $unreadNotifikasi = 0;
        if (Auth::check()) {
            $unreadNotifikasi = Notifikasi::query()
                ->where('user_id', Auth::id())
                ->where('dibaca', false)
                ->count();
        }

        $view->with('profil', $profil);
        $view->with('settings', $settings);
        $view->with('unreadNotifikasi', $unreadNotifikasi);
    }
}
