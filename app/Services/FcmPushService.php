<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendFcmPushJob;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
    public function isConfigured(): bool
    {
        $projectId = (string) config('services.fcm.project_id', '');
        $credentials = (string) config('services.fcm.credentials', '');

        return $projectId !== ''
            && $credentials !== ''
            && is_readable($credentials);
    }

    public function registerToken(User $user, string $token, string $platform = 'android', ?string $deviceName = null): DeviceToken
    {
        return DeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]
        );
    }

    public function unregisterToken(User $user, string $token): void
    {
        DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->delete();
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToUser(User|int $user, string $title, string $body, array $data = []): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        foreach ($tokens as $token) {
            SendFcmPushJob::dispatch($token, $title, $body, $data);
        }
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::debug('FCM skipped: credentials not configured.');

            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            $projectId = (string) config('services.fcm.project_id');

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => collect($data)
                        ->map(fn ($value) => (string) $value)
                        ->all(),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'sd_warialau_channel',
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    $payload
                );

            if ($response->failed()) {
                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                // Invalid token — remove it
                if (in_array($response->status(), [404, 400], true)) {
                    DeviceToken::query()->where('token', $token)->delete();
                }

                return false;
            }

            DeviceToken::query()->where('token', $token)->update(['last_used_at' => now()]);

            return true;
        } catch (Throwable $e) {
            Log::error('FCM send exception: '.$e->getMessage());

            return false;
        }
    }

    private function getAccessToken(): string
    {
        $credentialsPath = (string) config('services.fcm.credentials');
        $json = json_decode((string) file_get_contents($credentialsPath), true, 512, JSON_THROW_ON_ERROR);

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claim = $this->base64UrlEncode(json_encode([
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claim;
        $privateKey = openssl_pkey_get_private($json['private_key']);
        if ($privateKey === false) {
            throw new \RuntimeException('Invalid FCM private key.');
        }

        openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed() || ! isset($response['access_token'])) {
            throw new \RuntimeException('Unable to obtain FCM access token.');
        }

        return (string) $response['access_token'];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
