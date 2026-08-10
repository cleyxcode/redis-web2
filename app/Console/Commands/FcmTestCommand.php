<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Console\Command;

class FcmTestCommand extends Command
{
    protected $signature = 'fcm:test
                            {user? : ID user tujuan}
                            {--token= : Kirim ke token FCM spesifik}';

    protected $description = 'Uji konfigurasi FCM dan kirim push percobaan';

    public function handle(FcmPushService $fcm): int
    {
        if (! $fcm->isConfigured()) {
            $this->error('FCM belum dikonfigurasi.');
            $this->line('Set FCM_PROJECT_ID dan FCM_CREDENTIALS (path ke firebase-credentials.json).');

            return self::FAILURE;
        }

        $this->info('FCM credentials OK.');

        $token = $this->option('token');
        if (is_string($token) && $token !== '') {
            $ok = $fcm->sendToToken($token, 'Tes FCM', 'Push percobaan dari artisan fcm:test');
            $this->line($ok ? 'Push ke token: OK' : 'Push ke token: GAGAL (cek log)');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $userId = $this->argument('user');
        if ($userId === null) {
            $this->comment('Tidak ada tujuan. Contoh:');
            $this->line('  php artisan fcm:test 2');
            $this->line('  php artisan fcm:test --token=DEVICE_TOKEN');

            return self::SUCCESS;
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            $this->error("User #{$userId} tidak ditemukan.");

            return self::FAILURE;
        }

        $fcm->sendToUser($user, 'Tes FCM', 'Push percobaan untuk '.$user->name);
        $this->info("Push di-dispatch ke antrian untuk user #{$user->id} ({$user->email}).");
        $this->comment('Pastikan queue worker berjalan: php artisan queue:work');

        return self::SUCCESS;
    }
}
