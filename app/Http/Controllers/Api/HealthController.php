<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => false,
            'cache' => false,
            'queue' => false,
        ];
        $errors = [];

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
            // Ensure queue connection is reachable (database/redis)
            Queue::connection()->size();
            $checks['queue'] = true;
        } catch (Throwable $e) {
            $errors['queue'] = $e->getMessage();
        }

        $ok = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }
}
