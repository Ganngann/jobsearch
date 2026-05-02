<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\MatchingService;

foreach(User::all() as $user) {
    echo "Matching for user: " . $user->email . "\n";
    app(MatchingService::class)->triggerMassMatch($user);
}
echo "Done.\n";
