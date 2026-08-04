<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $faqs = Cache::remember('faqs_aktif', now()->addHours(6), function () {
            return Faq::query()
                ->aktif()
                ->ordered()
                ->get(['id', 'pertanyaan', 'jawaban', 'urutan']);
        });

        return response()->json(['data' => $faqs]);
    }
}
