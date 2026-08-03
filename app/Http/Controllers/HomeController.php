<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\InfoPendaftaran;
use App\Models\ProfilSekolah;
use App\Models\Setting;
use App\Models\Siswa;
use App\Support\MapEmbed;

class HomeController extends Controller
{
    public function index()
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

        $jumlahGuru = Guru::where('status', 'aktif')->count();
        $jumlahSiswa = Siswa::where('status', 'aktif')->count();

        $beritaTerbaru = Berita::where('status', 'publish')
            ->latest('tanggal_publish')
            ->take(3)
            ->get();

        $galeri = Galeri::latest()->take(6)->get();

        $pendaftaranAktif = InfoPendaftaran::where('status', 'aktif')->first();

        $banners = Banner::where('status', 'aktif')->orderBy('urutan')->get();

        return view('pages.home', compact(
            'profil',
            'settings',
            'jumlahGuru',
            'jumlahSiswa',
            'beritaTerbaru',
            'galeri',
            'pendaftaranAktif',
            'banners',
        ));
    }
}
