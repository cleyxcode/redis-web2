<?php

declare(strict_types=1);

namespace Tests\Feature\Redis;

use App\Services\BeritaService;
use App\Services\GuruService;
use App\Services\ProfilSekolahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class RedisServiceCacheTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'redis']);
        Redis::connection('default')->flushdb();
        Redis::connection('cache')->flushdb();
        DB::table('cache')->delete();
    }

    public function test_guru_service_caches_in_redis(): void
    {
        $this->createGuru(['nama' => 'Ibu Maria']);

        $service = app(GuruService::class);
        $first = $service->getAll();
        $second = $service->getAll();

        $this->assertCount(1, $first);
        $this->assertEquals($first->pluck('id'), $second->pluck('id'));

        $keys = Redis::connection('cache')->keys('*guru_aktif*');
        $this->assertNotEmpty($keys);

        $mysqlHit = DB::table('cache')->where('key', 'like', '%guru_aktif%')->exists();
        $this->assertFalse($mysqlHit);
    }

    public function test_profil_sekolah_service_caches_in_redis(): void
    {
        $this->createProfilSekolah(['nama_sekolah' => 'SD Negeri Warialau']);

        $service = app(ProfilSekolahService::class);
        $profil = $service->getProfil();

        $this->assertSame('SD Negeri Warialau', $profil['nama_sekolah']);
        $this->assertNotEmpty(Redis::connection('cache')->keys('*profil_sekolah*'));
        $this->assertFalse(
            DB::table('cache')->where('key', 'like', '%profil_sekolah%')->exists()
        );
    }

    public function test_berita_service_caches_detail_in_redis(): void
    {
        $admin = $this->createAdmin();
        $berita = $this->createBerita($admin, ['judul' => 'Berita Redis']);

        $service = app(BeritaService::class);
        $found = $service->getById($berita->id);

        $this->assertSame('Berita Redis', $found->judul);
        $this->assertNotEmpty(Redis::connection('cache')->keys("*berita_{$berita->id}*"));
        $this->assertFalse(
            DB::table('cache')->where('key', 'like', "%berita_{$berita->id}%")->exists()
        );
    }

    public function test_without_redis_default_array_store_does_not_touch_redis(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $this->createGuru(['nama' => 'Pak Array']);
        app(GuruService::class)->getAll();

        $this->assertEmpty(Redis::connection('cache')->keys('*guru_aktif*'));
        $this->assertFalse(DB::table('cache')->where('key', 'like', '%guru_aktif%')->exists());
        $this->assertTrue(Cache::has('guru_aktif'));
    }

    public function test_database_cache_driver_stores_service_data_in_mysql(): void
    {
        config(['cache.default' => 'database']);
        Cache::flush();
        DB::table('cache')->delete();

        $this->createGuru(['nama' => 'Pak Database']);
        app(GuruService::class)->getAll();

        $this->assertTrue(DB::table('cache')->where('key', 'like', '%guru_aktif%')->exists());
        $this->assertEmpty(Redis::connection('cache')->keys('*guru_aktif*'));
    }
}
