<?php

namespace App\Services;

use App\Repositories\Contracts\GaleriRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class GaleriService
{
    public function __construct(private GaleriRepositoryInterface $repo) {}

    public function getAll(int $perPage = 12)
    {
        $page = request()->get('page', 1);

        return Cache::remember("galeri_page_{$page}_per{$perPage}", now()->addHours(1), function () use ($perPage) {
            return $this->repo->paginate($perPage);
        });
    }
}
