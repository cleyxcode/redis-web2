<?php

declare(strict_types=1);

/**
 * Benchmark service-layer: tanpa cache (DB) vs cache database vs cache Redis.
 * Dipanggil oleh scripts/benchmark-redis.sh — jangan dijalankan sendiri kecuali via Sail.
 */

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Repositories\Contracts\BeritaRepositoryInterface;
use App\Repositories\Contracts\GaleriRepositoryInterface;
use App\Repositories\Contracts\GuruRepositoryInterface;
use App\Repositories\Contracts\InfoPendaftaranRepositoryInterface;
use App\Repositories\Contracts\ProfilSekolahRepositoryInterface;
use App\Services\BeritaService;
use App\Services\GaleriService;
use App\Services\GuruService;
use App\Services\PendaftaranService;
use App\Services\ProfilSekolahService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$iterations = (int) ($argv[1] ?? 50);
if ($iterations < 5) {
    $iterations = 5;
}

function ms(float $startNs): float
{
    return (hrtime(true) - $startNs) / 1_000_000;
}

function avgMs(callable $fn, int $iterations): float
{
    // warm once (abaikan hasil)
    $fn();

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }

    return ms($start) / $iterations;
}

function fmt(float $ms): string
{
    return number_format($ms, 3, '.', '');
}

function pctFaster(float $slow, float $fast): string
{
    if ($slow <= 0) {
        return '0%';
    }

    return number_format((($slow - $fast) / $slow) * 100, 1, '.', '').'%';
}

// Pastikan Redis hidup
try {
    $pong = Redis::connection('cache')->ping();
    $redisOk = $pong === true || $pong === 'PONG' || $pong === 1;
} catch (Throwable $e) {
    fwrite(STDERR, "GAGAL: Redis tidak bisa dihubungi — {$e->getMessage()}\n");
    exit(1);
}

if (! $redisOk) {
    fwrite(STDERR, "GAGAL: Redis ping gagal\n");
    exit(1);
}

Redis::connection('cache')->flushdb();
DB::table('cache')->delete();
Cache::store('redis')->flush();
Cache::store('database')->flush();

$guruRepo = app(GuruRepositoryInterface::class);
$beritaRepo = app(BeritaRepositoryInterface::class);
$profilRepo = app(ProfilSekolahRepositoryInterface::class);
$galeriRepo = app(GaleriRepositoryInterface::class);
$infoRepo = app(InfoPendaftaranRepositoryInterface::class);

$counts = [
    'guru_aktif' => Guru::where('status', 'aktif')->count(),
    'berita_publish' => Berita::where('status', 'publish')->count(),
    'galeri' => Galeri::count(),
];

$cases = [
    [
        'label' => 'Guru aktif (semua)',
        'key' => 'guru_aktif',
        'db' => fn () => $guruRepo->allAktif(),
        'warm' => fn () => app(GuruService::class)->getAll(),
    ],
    [
        'label' => 'Berita terbaru (5)',
        'key' => 'berita_latest_5',
        'db' => fn () => $beritaRepo->latest(5),
        'warm' => fn () => app(BeritaService::class)->getLatest(5),
    ],
    [
        'label' => 'Berita latest 100',
        'key' => 'bench_berita_latest_100',
        'db' => fn () => $beritaRepo->latest(100),
        'warm' => fn () => Cache::store('redis')->remember(
            'bench_berita_latest_100',
            600,
            fn () => $beritaRepo->latest(100)
        ),
    ],
    [
        'label' => 'Berita page per100',
        'key' => 'bench_berita_page_100',
        'db' => fn () => $beritaRepo->paginate(100),
        'warm' => fn () => Cache::store('redis')->remember(
            'bench_berita_page_100',
            600,
            fn () => $beritaRepo->paginate(100)
        ),
    ],
    [
        'label' => 'Berita 100 kolom ringan',
        'key' => 'bench_berita_lean_100',
        'db' => fn () => Berita::query()
            ->where('status', 'publish')
            ->select(['id', 'judul', 'kategori', 'tanggal_publish', 'status'])
            ->latest('tanggal_publish')
            ->limit(100)
            ->get(),
        'warm' => fn () => Cache::store('redis')->remember(
            'bench_berita_lean_100',
            600,
            fn () => Berita::query()
                ->where('status', 'publish')
                ->select(['id', 'judul', 'kategori', 'tanggal_publish', 'status'])
                ->latest('tanggal_publish')
                ->limit(100)
                ->get()
        ),
    ],
    [
        'label' => 'Guru 500 kolom ringan',
        'key' => 'bench_guru_lean_500',
        'db' => fn () => Guru::query()
            ->where('status', 'aktif')
            ->select(['id', 'nama', 'nip', 'jabatan', 'mata_pelajaran', 'status'])
            ->orderBy('nama')
            ->get(),
        'warm' => fn () => Cache::store('redis')->remember(
            'bench_guru_lean_500',
            600,
            fn () => Guru::query()
                ->where('status', 'aktif')
                ->select(['id', 'nama', 'nip', 'jabatan', 'mata_pelajaran', 'status'])
                ->orderBy('nama')
                ->get()
        ),
    ],
    [
        'label' => '200x hitung guru',
        'key' => 'bench_repeat_payload',
        'db' => function () {
            // Simulasi beban berulang tanpa cache
            $total = 0;
            for ($i = 0; $i < 200; $i++) {
                $total += Guru::where('status', 'aktif')->count();
            }

            return $total;
        },
        'warm' => function () {
            $value = Guru::where('status', 'aktif')->count();
            Cache::store('redis')->put('bench_repeat_payload', $value, 600);

            return $value;
        },
    ],
    [
        'label' => 'Profil sekolah',
        'key' => 'profil_sekolah',
        'db' => fn () => $profilRepo->get(),
        'warm' => fn () => app(ProfilSekolahService::class)->getProfil(),
    ],
    [
        'label' => 'Galeri page 1',
        'key' => 'galeri_page_1_per12',
        'db' => fn () => $galeriRepo->paginate(12),
        'warm' => fn () => app(GaleriService::class)->getAll(12),
    ],
    [
        'label' => 'Galeri per 100',
        'key' => 'bench_galeri_per100',
        'db' => fn () => $galeriRepo->paginate(100),
        'warm' => fn () => Cache::store('redis')->remember(
            'bench_galeri_per100',
            600,
            fn () => $galeriRepo->paginate(100)
        ),
    ],
    [
        'label' => 'Info pendaftaran',
        'key' => 'info_pendaftaran_aktif',
        'db' => fn () => $infoRepo->getActive(),
        'warm' => fn () => app(PendaftaranService::class)->getInfoAktif(),
    ],
];

$rows = [];

foreach ($cases as $case) {
    // 1) Langsung database (tanpa cache)
    $dbMs = avgMs($case['db'], $iterations);

    // 2) Cache database (MySQL table `cache`)
    Cache::store('database')->forget($case['key']);
    Cache::store('database')->remember($case['key'], 600, $case['db']);
    $dbCacheMs = avgMs(fn () => Cache::store('database')->get($case['key']), $iterations);

    // 3) Cache Redis — warm via service (Cache::remember default = redis)
    config(['cache.default' => 'redis']);
    Cache::store('redis')->forget($case['key']);
    ($case['warm'])();
    $redisMs = avgMs(fn () => Cache::store('redis')->get($case['key']), $iterations);

    $rows[] = [
        'label' => $case['label'],
        'key' => $case['key'],
        'db' => $dbMs,
        'db_cache' => $dbCacheMs,
        'redis' => $redisMs,
        'exists' => Cache::store('redis')->has($case['key']),
    ];
}

$keys = Redis::connection('cache')->keys('*');
sort($keys);

$payload = [
    'iterations' => $iterations,
    'counts' => $counts,
    'redis_ok' => true,
    'redis_key_count' => count($keys),
    'redis_keys' => $keys,
    'rows' => array_map(static function (array $r): array {
        return [
            'label' => $r['label'],
            'key' => $r['key'],
            'db_ms' => round($r['db'], 3),
            'db_cache_ms' => round($r['db_cache'], 3),
            'redis_ms' => round($r['redis'], 3),
            'lebih_cepat_vs_db' => pctFaster($r['db'], $r['redis']),
            'lebih_cepat_vs_db_cache' => pctFaster($r['db_cache'], $r['redis']),
            'ada_di_redis' => $r['exists'],
        ];
    }, $rows),
    'avg' => [
        'db_ms' => round(array_sum(array_column($rows, 'db')) / count($rows), 3),
        'db_cache_ms' => round(array_sum(array_column($rows, 'db_cache')) / count($rows), 3),
        'redis_ms' => round(array_sum(array_column($rows, 'redis')) / count($rows), 3),
    ],
];

$payload['avg']['lebih_cepat_vs_db'] = pctFaster($payload['avg']['db_ms'], $payload['avg']['redis_ms']);
$payload['avg']['lebih_cepat_vs_db_cache'] = pctFaster($payload['avg']['db_cache_ms'], $payload['avg']['redis_ms']);

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
