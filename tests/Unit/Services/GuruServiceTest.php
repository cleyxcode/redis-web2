<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Guru;
use App\Repositories\Contracts\GuruRepositoryInterface;
use App\Services\GuruService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class GuruServiceTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_get_all_returns_active_gurus_and_caches(): void
    {
        $this->createGuru(['nama' => 'Guru A', 'status' => 'aktif']);
        $this->createGuru(['nama' => 'Guru B', 'status' => 'nonaktif']);

        $service = app(GuruService::class);
        $result = $service->getAll();

        $this->assertCount(1, $result);
        $this->assertSame('Guru A', $result->first()->nama);
        $this->assertTrue(Cache::has('guru_aktif'));
    }

    public function test_get_all_uses_cache_on_second_call(): void
    {
        $guru = new Guru(['nama' => 'Cached Guru']);
        $guru->id = 1;

        $repo = Mockery::mock(GuruRepositoryInterface::class);
        $repo->shouldReceive('allAktif')->once()->andReturn(new Collection([$guru]));

        $this->app->instance(GuruRepositoryInterface::class, $repo);

        $service = app(GuruService::class);
        $first = $service->getAll();
        $second = $service->getAll();

        $this->assertSame($first, $second);
    }
}
