<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FcmPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(FcmPushService $fcm): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => false,
            'cache' => false,
            'queue' => false,
            'fcm' => $fcm->isConfigured(),
            'sentry' => filled(config('sentry.dsn')),
        ];
        $errors = [];
        $meta = [
            'queue_pending' => null,
            'queue_failed' => null,
        ];

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            $checks['database'] = true;
        } catch (Throwable $e) {
            $errors['database'] = $e->getMessage();
        }

        try {
            $key = 'healthcheck_'.uniqid('', true);
            Cache::put($key, 'ok', 10);
            $checks['cache'] = Cache::pull($key) === 'ok';
            if (! $checks['cache']) {
                $errors['cache'] = 'Cache read/write failed';
            }
        } catch (Throwable $e) {
            $errors['cache'] = $e->getMessage();
        }

        try {
            Queue::connection()->size();
            $checks['queue'] = true;
            $meta['queue_pending'] = (int) DB::table('jobs')->count();
            $meta['queue_failed'] = (int) DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            $errors['queue'] = $e->getMessage();
        }

        if (! $checks['fcm']) {
            $errors['fcm'] = 'FCM_PROJECT_ID / FCM_CREDENTIALS belum diset';
        }

        // FCM & Sentry bersifat opsional — tidak membuat health 503.
        $requiredOk = $checks['app'] && $checks['database'] && $checks['cache'] && $checks['queue'];

        return response()->json([
            'status' => $requiredOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'meta' => $meta,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $requiredOk ? 200 : 503);
    }
}
