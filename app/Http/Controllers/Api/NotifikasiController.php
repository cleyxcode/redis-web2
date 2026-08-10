<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotifikasiController extends Controller
{
    public function __construct(private NotifikasiService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->getAll((int) $request->user()->id);

        // Keep classic Laravel paginator JSON shape expected by the mobile app.
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($item) => (new NotifikasiResource($item))->resolve()
            )
        );

        return response()->json($paginator);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread' => $this->service->countUnread((int) $request->user()->id),
        ]);
    }

    public function baca(Request $request, int $id): JsonResponse
    {
        $updated = $this->service->markAsRead($id, (int) $request->user()->id);

        if (! $updated) {
            throw new NotFoundHttpException('Notifikasi tidak ditemukan.');
        }

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function bacaSemua(Request $request): JsonResponse
    {
        $this->service->markAllAsRead((int) $request->user()->id);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = $this->service->deleteForUser($id, (int) $request->user()->id);

        if (! $deleted) {
            throw new NotFoundHttpException('Notifikasi tidak ditemukan.');
        }

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }
}
