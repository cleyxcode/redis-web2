<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\FcmPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFcmPushJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(FcmPushService $fcm): void
    {
        $ok = $fcm->sendToToken($this->token, $this->title, $this->body, $this->data);

        if (! $ok && $fcm->isConfigured()) {
            Log::channel('stack')->warning('FCM push returned false', [
                'token_suffix' => substr($this->token, -8),
                'title' => $this->title,
                'data' => $this->data,
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('FCM push job failed permanently', [
            'token_suffix' => substr($this->token, -8),
            'title' => $this->title,
            'data' => $this->data,
            'error' => $exception?->getMessage(),
        ]);

        if (config('sentry.dsn')) {
            \Sentry\captureException(
                $exception ?? new \RuntimeException('FCM push job failed')
            );
        }
    }
}
