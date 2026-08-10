<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeritaController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\GuruController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PendaftaranController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProfilSekolahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — SD Negeri Warialau
|--------------------------------------------------------------------------
| Base URL: /api/v1
| Auth: Laravel Sanctum (Bearer Token)
*/

Route::prefix('v1')->middleware('throttle:api')->group(function () {

    Route::get('health', HealthController::class);

    // ── Public ──────────────────────────────────────────────────────
    Route::get('profil-sekolah', [ProfilSekolahController::class, 'profil']);
    Route::get('banner', [ProfilSekolahController::class, 'banner']);
    Route::get('faq', [FaqController::class, 'index']);
    Route::get('guru', [GuruController::class, 'index']);
    Route::get('berita', [BeritaController::class, 'index']);
    Route::get('berita/{id}', [BeritaController::class, 'show']);
    Route::get('galeri', [GaleriController::class, 'index']);
    Route::get('info-pendaftaran', [PendaftaranController::class, 'infoAktif']);

    // ── Auth ─────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth');
        Route::post('forgot-password', [AuthController::class, 'sendOtp'])
            ->middleware('throttle:otp');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:otp');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // ── Protected (auth:sanctum) ──────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile/info', [ProfileController::class, 'updateInfo']);
        Route::patch('profile/password', [ProfileController::class, 'updatePassword']);

        Route::post('pendaftaran', [PendaftaranController::class, 'store'])
            ->middleware('throttle:pendaftaran');
        Route::get('pendaftaran/riwayat', [PendaftaranController::class, 'riwayat']);
        Route::get('pendaftaran/{id}', [PendaftaranController::class, 'show']);

        Route::get('notifikasi', [NotifikasiController::class, 'index']);
        Route::get('notifikasi/unread-count', [NotifikasiController::class, 'unreadCount']);
        Route::patch('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
        Route::patch('notifikasi/{id}/baca', [NotifikasiController::class, 'baca']);
        Route::delete('notifikasi/{id}', [NotifikasiController::class, 'destroy']);

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    });
});
