<?php

use App\Models\JobOffer;
use App\Models\User;
use App\Services\VectorService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::find(2);
$jobs = JobOffer::whereNull('vector_embedding')
    ->where('status', 'active')
    ->where('is_detailed', true)
    ->inRandomOrder()
    ->limit(200)
    ->get();

$vectorService = app(VectorService::class);
$count = 0;

echo "Démarrage de la vectorisation pour " . $jobs->count() . " offres...\n";

foreach ($jobs as $job) {
    try {
        if ($vectorService->updateJobVector($job)) {
            if ($user && $user->vector_embedding) {
                $score = $vectorService->cosineSimilarity($user->vector_embedding, $job->vector_embedding);
                $user->matches()->updateOrCreate(
                    ['job_offer_id' => $job->id],
                    ['vector_score' => $score]
                );
            }
            $count++;
            if ($count % 5 == 0) {
                echo "Progression : $count/200\n";
            }
        }
        usleep(800000); // 0.8s pour être safe avec les quotas
    } catch (\Exception $e) {
        Log::error("Erreur lors de la vectorisation de l'offre #{$job->id}: " . $e->getMessage());
    }
}

echo "\nTerminé ! $count offres vectorisées.\n";
