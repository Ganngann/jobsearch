<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Create dummy users if not enough
$count = User::count();
if ($count < 5000) {
    echo "Creating dummy users...\n";
    User::factory()->count(5000 - $count)->create();
}

echo "Measuring User::all() ...\n";
$startMemoryAll = memory_get_usage();
$startTimeAll = microtime(true);
$usersAll = User::all();
foreach ($usersAll as $user) {
    // simulated work
}
$endTimeAll = microtime(true);
$endMemoryAll = memory_get_usage();

unset($usersAll);
gc_collect_cycles();

echo "Measuring User::chunk() ...\n";
$startMemoryChunk = memory_get_usage();
$startTimeChunk = microtime(true);
User::chunk(200, function ($users) {
    foreach ($users as $user) {
        // simulated work
    }
});
$endTimeChunk = microtime(true);
$endMemoryChunk = memory_get_usage();

printf("User::all()   - Memory: %.2f MB, Time: %.2f seconds\n", ($endMemoryAll - $startMemoryAll) / 1024 / 1024, $endTimeAll - $startTimeAll);
printf("User::chunk() - Memory: %.2f MB, Time: %.2f seconds\n", ($endMemoryChunk - $startMemoryChunk) / 1024 / 1024, $endTimeChunk - $startTimeChunk);
