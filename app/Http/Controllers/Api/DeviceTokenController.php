<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FcmPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(private FcmPushService $fcm) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'in:android,ios,other'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $device = $this->fcm->registerToken(
            $request->user(),
            $data['token'],
            $data['platform'] ?? 'android',
            $data['device_name'] ?? null,
        );

        return response()->json([
            'message' => 'Device token tersimpan.',
            'id' => $device->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $this->fcm->unregisterToken($request->user(), $data['token']);

        return response()->json(['message' => 'Device token dihapus.']);
    }
}
