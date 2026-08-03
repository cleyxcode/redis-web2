<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Berita;
use App\Models\Pendaftaran;
use App\Observers\BeritaObserver;
use App\Observers\PendaftaranObserver;
use App\View\Composers\LayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Berita::observe(BeritaObserver::class);
        Pendaftaran::observe(PendaftaranObserver::class);

        View::composer(['layouts.app', 'layouts.auth', 'pages.profil'], LayoutComposer::class);

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.',
                ], 429);
            });
        });

        RateLimiter::for('otp', function (Request $request) {
            $key = strtolower((string) $request->input('email', $request->ip()));

            return Limit::perMinute(3)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak permintaan OTP. Coba lagi nanti.',
                ], 429);
            });
        });

        RateLimiter::for('pendaftaran', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(3)->by((string) $key)->response(function () {
                return response()->json([
                    'message' => 'Terlalu banyak pengajuan pendaftaran. Coba lagi nanti.',
                ], 429);
            });
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
