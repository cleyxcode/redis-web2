<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
    echo "Cache put success\n";
    $v = Illuminate\Support\Facades\Cache::get('test_key');
    echo "Get value: $v\n";
    echo "Default driver: " . config('cache.default') . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
