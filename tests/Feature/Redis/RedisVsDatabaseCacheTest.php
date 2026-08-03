<?php

declare(strict_types=1);

namespace Tests\Feature\Redis;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisVsDatabaseCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::connection('default')->flushdb();
        Redis::connection('cache')->flushdb();
        DB::table('cache')->delete();
    }

    public function test_redis_connection_is_alive(): void
    {
        $pong = Redis::connection('default')->ping();

        $this->assertTrue($pong === true || $pong === 'PONG' || $pong === 1);
    }

    public function test_redis_store_writes_to_redis_not_mysql(): void
    {
        Cache::store('redis')->put('cmp_redis_key', 'nilai-redis', 60);

        $this->assertSame('nilai-redis', Cache::store('redis')->get('cmp_redis_key'));
        $this->assertTrue(Cache::store('redis')->has('cmp_redis_key'));

        $mysqlHit = DB::table('cache')
            ->where('key', 'like', '%cmp_redis_key%')
            ->exists();

        $this->assertFalse($mysqlHit, 'Data cache redis tidak boleh masuk tabel MySQL cache');

        $keys = Redis::connection('cache')->keys('*cmp_redis_key*');
        $this->assertNotEmpty($keys, 'Key harus ada di Redis cache DB');
    }

    public function test_database_store_writes_to_mysql_not_redis(): void
    {
        Cache::store('database')->put('cmp_db_key', 'nilai-database', 60);

        $this->assertSame('nilai-database', Cache::store('database')->get('cmp_db_key'));

        $mysqlHit = DB::table('cache')
            ->where('key', 'like', '%cmp_db_key%')
            ->exists();

        $this->assertTrue($mysqlHit, 'Data cache database harus masuk tabel MySQL cache');

        $keys = Redis::connection('cache')->keys('*cmp_db_key*');
        $this->assertEmpty($keys, 'Key cache database tidak boleh ada di Redis');
    }

    public function test_redis_and_database_stores_are_isolated(): void
    {
        Cache::store('redis')->put('shared_name', 'from-redis', 60);
        Cache::store('database')->put('shared_name', 'from-database', 60);

        $this->assertSame('from-redis', Cache::store('redis')->get('shared_name'));
        $this->assertSame('from-database', Cache::store('database')->get('shared_name'));
        $this->assertNotSame(
            Cache::store('redis')->get('shared_name'),
            Cache::store('database')->get('shared_name')
        );
    }

    public function test_redis_forget_removes_only_redis_data(): void
    {
        Cache::store('redis')->put('forget_me', 'r', 60);
        Cache::store('database')->put('forget_me', 'd', 60);

        Cache::store('redis')->forget('forget_me');

        $this->assertNull(Cache::store('redis')->get('forget_me'));
        $this->assertSame('d', Cache::store('database')->get('forget_me'));
    }

    public function test_redis_is_faster_than_database_cache_for_repeated_reads(): void
    {
        $payload = ['school' => 'SD Negeri Warialau', 'items' => range(1, 100)];

        Cache::store('redis')->put('bench_payload', $payload, 120);
        Cache::store('database')->put('bench_payload', $payload, 120);

        $iterations = 100;

        $redisStart = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::store('redis')->get('bench_payload');
        }
        $redisMs = (hrtime(true) - $redisStart) / 1_000_000;

        $dbStart = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            Cache::store('database')->get('bench_payload');
        }
        $dbMs = (hrtime(true) - $dbStart) / 1_000_000;

        $this->assertLessThan(
            $dbMs,
            $redisMs,
            sprintf('Redis (%.2f ms) seharusnya lebih cepat dari database cache (%.2f ms)', $redisMs, $dbMs)
        );
    }

    public function test_direct_redis_facade_roundtrip(): void
    {
        Redis::set('direct_test_key', 'hello-sail-redis');

        $this->assertSame('hello-sail-redis', Redis::get('direct_test_key'));

        Redis::del('direct_test_key');
        $this->assertNull(Redis::get('direct_test_key'));
    }
}
