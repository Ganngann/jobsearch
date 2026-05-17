<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\VectorService::class);
$method = new ReflectionMethod($service, 'buildJobString');
$method->setAccessible(true);

$job = \App\Models\JobOffer::factory()->make(['title' => '', 'description' => '', 'is_detailed' => true]);

echo $method->invoke($service, $job) . "\n";
