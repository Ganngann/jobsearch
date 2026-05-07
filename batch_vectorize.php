<?php

use App\Models\JobOffer;
use App\Models\User;
use App\Services\VectorService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ini_set('memory_limit', '512M');

// On cible Morgan (ID 3)
$user = User::find(3);
$jobs = JobOffer::whereNull('vector_embedding')
    ->where('status', 'active')
    ->where('is_detailed', true)
    ->limit(100) // Lot de 100 comme demandé
    ->cursor();

$vectorService = app(VectorService::class);
$count = 0;
$upsertData = [];

echo "Démarrage de la vectorisation (Lot de 100)...\n";

foreach ($jobs as $job) {
    $success = false;
    while (!$success) {
        try {
            if ($vectorService->updateJobVector($job)) {
                if ($user && $user->vector_embedding) {
                    $score = $vectorService->cosineSimilarity($user->vector_embedding, $job->vector_embedding);
                    
                    $upsertData[] = [
                        'user_id' => $user->id,
                        'job_offer_id' => $job->id,
                        'vector_score' => $score
                    ];
                }
                $count++;
                
                // Sync des scores par paquets de 20
                if (count($upsertData) >= 20) {
                    \App\Models\UserMatch::upsert($upsertData, ['user_id', 'job_offer_id'], ['vector_score']);
                    $upsertData = [];
                    echo "Sync DB : $count/100\n";
                }

                if ($count % 5 == 0) {
                    echo "Progression : $count/100\n";
                }
            }
            $success = true; // On passe à l'offre suivante
        } catch (\RuntimeException $e) {
            echo "Base de données occupée, pause de 5s avant nouvelle tentative...\n";
            sleep(5);
            // On ne passe pas success à true, donc le while recommence pour la MÊME offre
        }
    }
    
    // Petite pause pour les quotas et la DB
    usleep(500000); 
}

// Derniers scores
if (!empty($upsertData)) {
    \App\Models\UserMatch::upsert($upsertData, ['user_id', 'job_offer_id'], ['vector_score']);
}

echo "\nTerminé ! $count offres vectorisées avec succès.\n";
