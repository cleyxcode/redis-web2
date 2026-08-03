<?php

namespace App\View\Composers;

use App\Models\ProfilSekolah;
use App\Models\Setting;
use App\Support\MapEmbed;
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

        $view->with('profil', $profil);
        $view->with('settings', $settings);
    }
}
