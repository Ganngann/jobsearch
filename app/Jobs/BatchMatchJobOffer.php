<?php

namespace App\Jobs;

use App\Models\JobOffer;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class BatchMatchJobOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobOffer;

    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer;
    }

    public function handle(MatchingService $matchingService): void
    {
        if (!$this->jobOffer->metier_id) return;

        // On récupère les utilisateurs intéressés par ce métier
        $users = User::whereHas('preferredMetiers', function($q) {
            $q->where('metiers.id', $this->jobOffer->metier_id);
        })->get();

        foreach ($users as $user) {
            // On calcule le match un par un pour ne pas verrouiller la base trop longtemps d'un coup
            $matchingService->match($user, $this->jobOffer);
            
            // On laisse respirer SQLite pour les requêtes web prioritaires
            usleep(50000); // 50ms
        }
    }
}
