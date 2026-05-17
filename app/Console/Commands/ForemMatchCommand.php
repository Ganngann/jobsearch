<?php

namespace App\Console\Commands;

use App\Models\JobOffer;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Console\Command;

class ForemMatchCommand extends Command
{
    protected $signature = 'forem:match {--user= : ID de l\'utilisateur spécifique}';
    protected $description = 'Calcule les scores de correspondance (Layer 1 et Layer 2) entre les utilisateurs et les offres';

    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        parent::__construct();
        $this->matchingService = $matchingService;
    }

    public function handle()
    {
        $userId = $this->option('user');
        $userQuery = $userId ? User::where('id', $userId) : User::query();

        if ($userQuery->count() === 0) {
            $this->error('Aucun utilisateur trouvé.');
            return;
        }

        if (JobOffer::count() === 0) {
            $this->error('Aucune offre d\'emploi trouvée. Lancez forem:sync d\'abord.');
            return;
        }

        $userQuery->chunk(200, function ($users) {
            JobOffer::chunk(200, function ($jobOffers) use ($users) {
                foreach ($users as $user) {
                    $this->info("Traitement de l'utilisateur : {$user->name}");

                    foreach ($jobOffers as $jobOffer) {
                        $this->line("  Offre #{$jobOffer->forem_id} : {$jobOffer->title}");
                        $this->matchingService->match($user, $jobOffer);
                    }
                }
            });
        });

        $this->info('Matching terminé !');
    }
}

