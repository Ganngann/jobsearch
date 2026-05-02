<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\JobOffer;
use App\Services\MatchingService;

$user = User::first();
$offer = JobOffer::find(1);
$service = app(MatchingService::class);
echo "Score for User 1 on Offer 1: " . $service->calculatePreScore($user, $offer) . "\n";
