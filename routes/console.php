<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler — jalankan via cron Hostinger:
| * * * * * cd /path/to/web_laravel_sd && php artisan schedule:run >> /dev/null 2>&1
|
| Plus queue worker (setiap menit, stop-when-empty agar aman di shared hosting):
| * * * * * cd /path/to/web_laravel_sd && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
|--------------------------------------------------------------------------
*/

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db')->daily()->at('01:30');
Schedule::command('backup:run')->weeklyOn(0, '02:00');
Schedule::command('backup:monitor')->daily()->at('03:00');
Schedule::command('queue:prune-failed --hours=168')->weeklyOn(1, '03:30');
Schedule::command('system:status')->weeklyOn(1, '04:00')->appendOutputTo(storage_path('logs/system-status.log'));
