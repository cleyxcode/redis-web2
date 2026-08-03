<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Berita;
use App\Models\Galeri;
use App\Models\Guru;
use App\Models\User;
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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class BenchmarkRedisDataCommand extends Command
{
    protected $signature = 'redis:benchmark
        {jumlah? : Jumlah data utama (berita/guru/galeri) — contoh: 1000}
        {--berita= : Target jumlah berita publish}
        {--guru= : Target jumlah guru aktif}
        {--galeri= : Target jumlah galeri}
        {--banner= : Target jumlah banner aktif}
        {--iterasi=30 : Berapa kali tiap kasus diukur}
        {--skip-seed : Lewati pengisian data, langsung benchmark}
        {--json= : Simpan laporan JSON ke path ini}';

    protected $description = 'Isi data hingga ribuan + bandingkan performa tanpa cache vs Redis (statistik jelas)';

    private const MAX_DATA = 10000;

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Benchmark Redis Cache — SD Negeri Warialau');
        $this->line('  Bandingkan: Tanpa Cache (DB)  |  Cache MySQL  |  Cache Redis');
        $this->newLine();

        if (! $this->assertRedisAlive()) {
            return self::FAILURE;
        }

        [$targetBerita, $targetGuru, $targetGaleri, $targetBanner, $iterations] = $this->resolveInputs();

        $this->table(
            ['Parameter', 'Nilai'],
            [
                ['Target berita publish', number_format($targetBerita)],
                ['Target guru aktif', number_format($targetGuru)],
                ['Target galeri', number_format($targetGaleri)],
                ['Target banner aktif', number_format($targetBanner)],
                ['Iterasi benchmark', number_format($iterations)],
                ['Batas maksimum / tabel', number_format(self::MAX_DATA)],
            ]
        );

        if (! $this->option('skip-seed')) {
            $this->seedData($targetBerita, $targetGuru, $targetGaleri, $targetBanner);
        } else {
            $this->components->warn('Lewati seed (--skip-seed). Memakai data yang sudah ada.');
        }

        $counts = $this->currentCounts();
        $this->newLine();
        $this->components->info('Volume data setelah seed');
        $this->table(
            ['Tabel', 'Jumlah sekarang'],
            [
                ['berita (publish)', number_format($counts['berita_publish'])],
                ['guru (aktif)', number_format($counts['guru_aktif'])],
                ['galeri', number_format($counts['galeri'])],
                ['banner (aktif)', number_format($counts['banner_aktif'])],
            ]
        );

        $this->components->info('Menjalankan benchmark...');
        $report = $this->runBenchmark($iterations, $counts);

        $this->renderStats($report);
        $this->persistJson($report);

        $this->newLine();
        $this->components->success('Selesai. Redis siap dipakai untuk demo/laporan.');

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int,2:int,3:int,4:int}
     */
    private function resolveInputs(): array
    {
        $defaultJumlah = 1000;

        $jumlahArg = $this->argument('jumlah');
        $jumlahOpt = null;

        if ($jumlahArg !== null && $jumlahArg !== '') {
            $jumlahOpt = (int) $jumlahArg;
        }

        // Mode interaktif jika tidak ada argumen/opsi jumlah
        $hasExplicit = $jumlahOpt !== null
            || $this->option('berita') !== null
            || $this->option('guru') !== null
            || $this->option('galeri') !== null
            || $this->option('banner') !== null;

        if (! $hasExplicit && $this->input->isInteractive()) {
            $this->line('Masukkan jumlah data (boleh ribuan, maks '.self::MAX_DATA.' per tabel).');
            $this->line('Tekan Enter untuk memakai nilai default.');
            $this->newLine();

            $jumlahOpt = (int) $this->ask('Jumlah data utama (berita & galeri)', (string) $defaultJumlah);
            $guruDefault = (string) max(100, (int) round($jumlahOpt / 2));
            $targetBerita = $this->askInt('Jumlah berita publish', $jumlahOpt);
            $targetGuru = $this->askInt('Jumlah guru aktif', (int) $guruDefault);
            $targetGaleri = $this->askInt('Jumlah galeri', $jumlahOpt);
            $targetBanner = $this->askInt('Jumlah banner aktif', 50);
            $iterations = $this->askInt('Iterasi pengukuran (semakin besar semakin akurat)', (int) $this->option('iterasi'));
        } else {
            $base = $this->clamp($jumlahOpt ?? $defaultJumlah);
            $targetBerita = $this->clamp((int) ($this->option('berita') ?? $base));
            $targetGuru = $this->clamp((int) ($this->option('guru') ?? max(100, (int) round($base / 2))));
            $targetGaleri = $this->clamp((int) ($this->option('galeri') ?? $base));
            $targetBanner = $this->clamp((int) ($this->option('banner') ?? 50));
            $iterations = max(5, (int) $this->option('iterasi'));
        }

        return [
            $this->clamp($targetBerita),
            $this->clamp($targetGuru),
            $this->clamp($targetGaleri),
            $this->clamp($targetBanner),
            max(5, $iterations),
        ];
    }

    private function askInt(string $question, int $default): int
    {
        return $this->clamp((int) $this->ask($question, (string) $default));
    }

    private function clamp(int $value): int
    {
        if ($value < 0) {
            return 0;
        }

        if ($value > self::MAX_DATA) {
            $this->components->warn("Nilai {$value} dipotong ke batas maksimum ".self::MAX_DATA);

            return self::MAX_DATA;
        }

        return $value;
    }

    private function assertRedisAlive(): bool
    {
        try {
            $pong = Redis::connection('cache')->ping();
            $ok = $pong === true || $pong === 'PONG' || $pong === 1;
        } catch (Throwable $e) {
            $this->components->error('Redis tidak bisa dihubungi: '.$e->getMessage());
            $this->line('Pastikan Sail/Redis jalan: ./vendor/bin/sail up -d');

            return false;
        }

        if (! $ok) {
            $this->components->error('Redis ping gagal.');

            return false;
        }

        $this->components->twoColumnDetail('Redis', 'OK (PONG)');
        $this->components->twoColumnDetail('CACHE_STORE', (string) config('cache.default'));

        return true;
    }

    private function seedData(int $targetBerita, int $targetGuru, int $targetGaleri, int $targetBanner): void
    {
        $adminId = User::query()->where('role', 'admin')->value('id')
            ?? User::query()->value('id');

        if (! $adminId) {
            $this->components->error('Tidak ada user di database. Jalankan db:seed dulu.');

            throw new \RuntimeException('Admin user missing');
        }

        $longHtml = '<p>'.str_repeat('Konten panjang uji Redis SD Negeri Warialau. ', 40).'</p>';
        $now = now();

        $this->newLine();
        $this->components->info('Mengisi data (bulk insert)...');

        $needGuru = max(0, $targetGuru - Guru::query()->where('status', 'aktif')->count());
        $needBerita = max(0, $targetBerita - Berita::query()->where('status', 'publish')->count());
        $needGaleri = max(0, $targetGaleri - Galeri::query()->count());
        $needBanner = max(0, $targetBanner - Banner::query()->where('status', 'aktif')->count());

        $this->line("  Tambah guru   : {$needGuru}");
        $this->line("  Tambah berita : {$needBerita}");
        $this->line("  Tambah galeri : {$needGaleri}");
        $this->line("  Tambah banner : {$needBanner}");

        $barTotal = max(1, $needGuru + $needBerita + $needGaleri + $needBanner);
        $bar = $this->output->createProgressBar($barTotal);
        $bar->start();

        DB::transaction(function () use (
            $adminId,
            $longHtml,
            $now,
            $needGuru,
            $needBerita,
            $needGaleri,
            $needBanner,
            $bar
        ): void {
            $mapel = ['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'PJOK', 'Agama', 'Seni Budaya'];
            $rows = [];
            for ($i = 1; $i <= $needGuru; $i++) {
                $rows[] = [
                    'nama' => 'Guru Uji '.Str::padLeft((string) $i, 5, '0').' '.Str::random(4),
                    'nip' => '19'.str_pad((string) (100000000000 + $i + random_int(0, 999)), 12, '0', STR_PAD_LEFT),
                    'jabatan' => 'Guru Kelas',
                    'mata_pelajaran' => $mapel[$i % count($mapel)],
                    'no_hp' => '08'.str_pad((string) random_int(100000000, 999999999), 10, '0', STR_PAD_LEFT),
                    'foto' => null,
                    'status' => 'aktif',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($rows) >= 200) {
                    Guru::query()->insert($rows);
                    $bar->advance(count($rows));
                    $rows = [];
                }
            }
            if ($rows !== []) {
                Guru::query()->insert($rows);
                $bar->advance(count($rows));
            }

            $kategori = ['umum', 'kegiatan', 'prestasi', 'pengumuman'];
            $rows = [];
            for ($i = 1; $i <= $needBerita; $i++) {
                $rows[] = [
                    'user_id' => $adminId,
                    'judul' => 'Berita Uji Redis #'.$i.' — '.Str::random(12),
                    'isi' => $longHtml,
                    'gambar' => null,
                    'kategori' => $kategori[$i % count($kategori)],
                    'tanggal_publish' => $now->copy()->subDays($i % 800)->toDateString(),
                    'status' => 'publish',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($rows) >= 100) {
                    Berita::query()->insert($rows);
                    $bar->advance(count($rows));
                    $rows = [];
                }
            }
            if ($rows !== []) {
                Berita::query()->insert($rows);
                $bar->advance(count($rows));
            }

            $rows = [];
            for ($i = 1; $i <= $needGaleri; $i++) {
                $rows[] = [
                    'user_id' => $adminId,
                    'judul' => 'Galeri Uji #'.$i,
                    'foto' => 'galeri/uji-'.$i.'.jpg',
                    'keterangan' => str_repeat('Keterangan galeri uji redis. ', 12),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($rows) >= 200) {
                    Galeri::query()->insert($rows);
                    $bar->advance(count($rows));
                    $rows = [];
                }
            }
            if ($rows !== []) {
                Galeri::query()->insert($rows);
                $bar->advance(count($rows));
            }

            $rows = [];
            for ($i = 1; $i <= $needBanner; $i++) {
                $rows[] = [
                    'judul' => 'Banner Uji #'.$i,
                    'gambar' => 'banner/uji-'.$i.'.jpg',
                    'urutan' => $i,
                    'status' => 'aktif',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (count($rows) >= 100) {
                    Banner::query()->insert($rows);
                    $bar->advance(count($rows));
                    $rows = [];
                }
            }
            if ($rows !== []) {
                Banner::query()->insert($rows);
                $bar->advance(count($rows));
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->components->success('Seed selesai.');
    }

    /**
     * @return array{berita_publish:int,guru_aktif:int,galeri:int,banner_aktif:int}
     */
    private function currentCounts(): array
    {
        return [
            'berita_publish' => Berita::query()->where('status', 'publish')->count(),
            'guru_aktif' => Guru::query()->where('status', 'aktif')->count(),
            'galeri' => Galeri::query()->count(),
            'banner_aktif' => Banner::query()->where('status', 'aktif')->count(),
        ];
    }

    /**
     * @param  array{berita_publish:int,guru_aktif:int,galeri:int,banner_aktif:int}  $counts
     * @return array<string, mixed>
     */
    private function runBenchmark(int $iterations, array $counts): array
    {
        config(['cache.default' => 'redis']);

        Redis::connection('cache')->flushdb();
        DB::table('cache')->delete();
        Cache::store('redis')->flush();
        Cache::store('database')->flush();

        $guruRepo = app(GuruRepositoryInterface::class);
        $beritaRepo = app(BeritaRepositoryInterface::class);
        $profilRepo = app(ProfilSekolahRepositoryInterface::class);
        $galeriRepo = app(GaleriRepositoryInterface::class);
        $infoRepo = app(InfoPendaftaranRepositoryInterface::class);

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
                'label' => 'Guru kolom ringan',
                'key' => 'bench_guru_lean',
                'db' => fn () => Guru::query()
                    ->where('status', 'aktif')
                    ->select(['id', 'nama', 'nip', 'jabatan', 'mata_pelajaran', 'status'])
                    ->orderBy('nama')
                    ->get(),
                'warm' => fn () => Cache::store('redis')->remember(
                    'bench_guru_lean',
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
                    $total = 0;
                    for ($i = 0; $i < 200; $i++) {
                        $total += Guru::query()->where('status', 'aktif')->count();
                    }

                    return $total;
                },
                'warm' => function () {
                    $value = Guru::query()->where('status', 'aktif')->count();
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
        $bar = $this->output->createProgressBar(count($cases));
        $bar->start();

        foreach ($cases as $case) {
            $dbMs = $this->avgMs($case['db'], $iterations);

            Cache::store('database')->forget($case['key']);
            Cache::store('database')->remember($case['key'], 600, $case['db']);
            $dbCacheMs = $this->avgMs(fn () => Cache::store('database')->get($case['key']), $iterations);

            Cache::store('redis')->forget($case['key']);
            ($case['warm'])();
            $redisMs = $this->avgMs(fn () => Cache::store('redis')->get($case['key']), $iterations);

            $rows[] = [
                'label' => $case['label'],
                'key' => $case['key'],
                'db_ms' => round($dbMs, 3),
                'db_cache_ms' => round($dbCacheMs, 3),
                'redis_ms' => round($redisMs, 3),
                'lebih_cepat_vs_db' => $this->pctFaster($dbMs, $redisMs),
                'lebih_cepat_vs_db_cache' => $this->pctFaster($dbCacheMs, $redisMs),
                'speedup_vs_db' => $dbMs > 0 ? round($dbMs / max($redisMs, 0.0001), 1) : 0.0,
                'ada_di_redis' => Cache::store('redis')->has($case['key']),
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $keys = Redis::connection('cache')->keys('*');
        sort($keys);

        $avgDb = $this->avgCol($rows, 'db_ms');
        $avgDbCache = $this->avgCol($rows, 'db_cache_ms');
        $avgRedis = $this->avgCol($rows, 'redis_ms');

        $best = collect($rows)->sortByDesc(fn (array $r) => (float) str_replace('%', '', $r['lebih_cepat_vs_db']))->first();
        $worst = collect($rows)->sortBy(fn (array $r) => (float) str_replace('%', '', $r['lebih_cepat_vs_db']))->first();

        return [
            'generated_at' => now()->toDateTimeString(),
            'iterations' => $iterations,
            'counts' => $counts,
            'redis_key_count' => count($keys),
            'redis_keys' => $keys,
            'rows' => $rows,
            'avg' => [
                'db_ms' => $avgDb,
                'db_cache_ms' => $avgDbCache,
                'redis_ms' => $avgRedis,
                'lebih_cepat_vs_db' => $this->pctFaster($avgDb, $avgRedis),
                'lebih_cepat_vs_db_cache' => $this->pctFaster($avgDbCache, $avgRedis),
                'speedup_vs_db' => $avgDb > 0 ? round($avgDb / max($avgRedis, 0.0001), 1) : 0.0,
            ],
            'best_case' => $best,
            'worst_case' => $worst,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderStats(array $report): void
    {
        $this->components->info('HASIL BENCHMARK (ms = milidetik, semakin kecil semakin cepat)');

        $tableRows = [];
        foreach ($report['rows'] as $r) {
            $tableRows[] = [
                $r['label'],
                number_format($r['db_ms'], 3).' ms',
                number_format($r['db_cache_ms'], 3).' ms',
                number_format($r['redis_ms'], 3).' ms',
                $r['lebih_cepat_vs_db'],
                $r['speedup_vs_db'].'x',
                $r['ada_di_redis'] ? 'YA' : 'TIDAK',
            ];
        }

        $this->table(
            ['Kasus', 'Tanpa Cache (DB)', 'Cache MySQL', 'Cache Redis', 'Redis vs DB', 'Speedup', 'Di Redis?'],
            $tableRows
        );

        $avg = $report['avg'];
        $this->newLine();
        $this->components->info('STATISTIK RINGKAS');
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Rata-rata tanpa cache (DB)', number_format($avg['db_ms'], 3).' ms'],
                ['Rata-rata cache MySQL', number_format($avg['db_cache_ms'], 3).' ms'],
                ['Rata-rata cache Redis', number_format($avg['redis_ms'], 3).' ms'],
                ['Redis lebih cepat vs DB', $avg['lebih_cepat_vs_db'].' ('.$avg['speedup_vs_db'].'x)'],
                ['Redis lebih cepat vs MySQL cache', $avg['lebih_cepat_vs_db_cache']],
                ['Jumlah key di Redis', (string) $report['redis_key_count']],
                ['Kasus terbaik', ($report['best_case']['label'] ?? '-').' ('.($report['best_case']['lebih_cepat_vs_db'] ?? '-').')'],
                ['Kasus terlemah', ($report['worst_case']['label'] ?? '-').' ('.($report['worst_case']['lebih_cepat_vs_db'] ?? '-').')'],
                ['Volume berita', number_format($report['counts']['berita_publish'])],
                ['Volume guru aktif', number_format($report['counts']['guru_aktif'])],
                ['Volume galeri', number_format($report['counts']['galeri'])],
            ]
        );

        $this->newLine();
        $this->line('  Arti kolom:');
        $this->line('  • Tanpa Cache (DB) = query langsung ke database');
        $this->line('  • Cache MySQL      = cache disimpan di tabel MySQL');
        $this->line('  • Cache Redis      = cache disimpan di Redis');
        $this->line('  • Speedup          = berapa kali lebih cepat (contoh 10x = 10 kali lipat)');
        $this->newLine();

        if ($report['redis_key_count'] > 0) {
            $this->components->info('Contoh key Redis + TTL (maks 12)');
            $prefix = (string) config('database.redis.options.prefix', '');
            foreach (array_slice($report['redis_keys'], 0, 12) as $key) {
                $logical = ($prefix !== '' && str_starts_with($key, $prefix))
                    ? substr($key, strlen($prefix))
                    : $key;
                $ttl = Redis::connection('cache')->ttl($logical);
                $this->line(sprintf('  • %-70s TTL=%s dtk', $key, (string) $ttl));
            }
            $this->line('  Cek manual: ./vendor/bin/sail exec redis redis-cli -n 1 KEYS \'*\'');
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function persistJson(array $report): void
    {
        $path = $this->option('json');
        if (! $path) {
            $path = storage_path('logs/redis-benchmark-'.now()->format('Ymd-His').'.json');
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->components->twoColumnDetail('Laporan JSON', $path);
    }

    private function avgMs(callable $fn, int $iterations): float
    {
        $fn();

        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $fn();
        }

        return ((hrtime(true) - $start) / 1_000_000) / $iterations;
    }

    private function pctFaster(float $slow, float $fast): string
    {
        if ($slow <= 0) {
            return '0%';
        }

        return number_format((($slow - $fast) / $slow) * 100, 1, '.', '').'%';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function avgCol(array $rows, string $key): float
    {
        if ($rows === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $sum += (float) $row[$key];
        }

        return round($sum / count($rows), 3);
    }
}
