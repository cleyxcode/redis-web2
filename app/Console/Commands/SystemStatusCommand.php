<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStatusCommand extends Command
{
    protected $signature = 'system:status';

    protected $description = 'Cek status queue, FCM, backup, dan komponen operasional';

    public function handle(FcmPushService $fcm): int
    {
        $this->info('=== Status Sistem SD Negeri Warialau ===');

        $queueConnection = (string) config('queue.default');
        $this->line("Queue driver : {$queueConnection}");

        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $this->line("Jobs pending : {$pending}");
            $this->line("Jobs failed  : {$failed}");
        } catch (Throwable $e) {
            $this->warn('Jobs table   : '.$e->getMessage());
        }

        $fcmOk = $fcm->isConfigured();
        $this->line('FCM configured: '.($fcmOk ? 'yes' : 'NO (set FCM_PROJECT_ID + FCM_CREDENTIALS)'));
        $this->line('Device tokens : '.DeviceToken::query()->count());
        $this->line('Orangtua users: '.User::query()->where('role', 'orangtua')->count());

        $sentry = (string) config('sentry.dsn');
        $this->line('Sentry DSN    : '.($sentry !== '' ? 'set' : 'empty'));

        $backupDisks = config('backup.backup.destination.disks', []);
        $this->line('Backup disks  : '.implode(', ', (array) $backupDisks));

        $this->newLine();
        $this->comment('Cron Hostinger (jadwalkan keduanya):');
        $this->line('* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('* * * * * cd '.base_path().' && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1');

        return self::SUCCESS;
    }
}
