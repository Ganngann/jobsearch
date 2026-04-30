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
        $users = $userId ? User::where('id', $userId)->get() : User::all();

        if ($users->isEmpty()) {
            $this->error('Aucun utilisateur trouvé.');
            return;
        }

        $jobOffers = JobOffer::all();
        if ($jobOffers->isEmpty()) {
            $this->error('Aucune offre d\'emploi trouvée. Lancez forem:sync d\'abord.');
            return;
        }

        foreach ($users as $user) {
            $this->info("Traitement de l'utilisateur : {$user->name}");

            foreach ($jobOffers as $jobOffer) {
                $this->line("  Offre #{$jobOffer->forem_id} : {$jobOffer->title}");
                $this->matchingService->match($user, $jobOffer);
            }
        }

        $this->info('Matching terminé !');
    }
}

