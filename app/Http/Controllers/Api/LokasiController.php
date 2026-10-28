<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use Illuminate\Http\JsonResponse;

class LokasiController extends Controller
{
    public function index(): JsonResponse
    {
        $lokasi = Lokasi::query()
            ->aktif()
            ->orderBy('urutan')
            ->get(['id', 'nama', 'alamat', 'latitude', 'longitude', 'maps_url']);

        return response()->json($lokasi);
    }
}
