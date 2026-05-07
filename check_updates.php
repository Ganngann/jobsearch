<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$offers = App\Models\JobOffer::whereNotNull('vector_embedding')->orderBy('updated_at', 'desc')->limit(10)->get();
echo "Dernières offres vectorisées :\n";
foreach($offers as $j) {
    echo "#{$j->id} (Forem: {$j->forem_id}) - {$j->updated_at}\n";
}
echo "\nTotal vectorisées : " . App\Models\JobOffer::whereNotNull('vector_embedding')->count() . "\n";
