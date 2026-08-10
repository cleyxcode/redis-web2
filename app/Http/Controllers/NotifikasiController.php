<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function __construct(private NotifikasiService $service) {}

    public function index(Request $request): View
    {
        $userId = (int) $request->user()->id;
        $filter = $request->string('filter')->toString();
        $perPage = 15;

        $query = $request->user()->notifikasi()->latest();

        if ($filter === 'belum') {
            $query->where('dibaca', false);
        }

        $notifikasi = $query->paginate($perPage)->withQueryString();
        $unread = $this->service->countUnread($userId);

        return view('pages.notifikasi.index', [
            'notifikasi' => $notifikasi,
            'unread' => $unread,
            'filter' => $filter === 'belum' ? 'belum' : 'semua',
        ]);
    }

    public function baca(Request $request, int $id): RedirectResponse
    {
        $updated = $this->service->markAsRead($id, (int) $request->user()->id);

        if (! $updated) {
            return back()->with('error', 'Notifikasi tidak ditemukan.');
        }

        $notif = $request->user()->notifikasi()->find($id);
        if ($notif && $notif->tipe === 'berita' && $notif->referensi_id) {
            return redirect()->route('berita.show', $notif->referensi_id);
        }
        if ($notif && $notif->tipe === 'pendaftaran' && $notif->referensi_id) {
            return redirect()->route('pendaftaran.detail', $notif->referensi_id);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $this->service->markAllAsRead((int) $request->user()->id);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $deleted = $this->service->deleteForUser($id, (int) $request->user()->id);

        if (! $deleted) {
            return back()->with('error', 'Notifikasi tidak ditemukan.');
        }

        return back()->with('success', 'Notifikasi dihapus.');
    }
}
