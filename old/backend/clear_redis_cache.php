<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== REDIS CACHE KEYS ===" . PHP_EOL;

try {
    $redis = Illuminate\Support\Facades\Redis::connection();
    $keys = $redis->keys('*resume*');
    
    echo 'Found ' . count($keys) . ' resume-related keys:' . PHP_EOL;
    foreach ($keys as $key) {
        echo 'Key: ' . $key . PHP_EOL;
    }
    
    // Clear all resume keys
    foreach ($keys as $key) {
        $redis->del($key);
        echo 'Cleared: ' . $key . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Redis not available or error: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== DONE ===" . PHP_EOL;
