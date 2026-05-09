<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ZipCode;
use Illuminate\Support\Facades\DB;

class MatchChunkJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->user->id . '_' . md5(json_encode($this->jobOfferIds));
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public \App\Models\User $user,
        public array $jobOfferIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(\App\Services\MatchingService $matchingService): void
    {
        // Préparation du contexte (Sac à Dos) pour éviter les N+1
        $context = [
            'skill_ids' => $this->user->validatedSkills()->pluck('skills.id')->toArray(),
            'permit_ids' => $this->user->permits()->pluck('permits.id')->toArray(),
            'language_ids' => $this->user->languages()->pluck('languages.id')->toArray(),
            'preferred_metiers' => $this->user->preferredMetiers,
            'preferred_families' => $this->user->preferredReferentielMetiers,
            'refused_skill_ids' => DB::table('user_skill')
                ->where('user_id', $this->user->id)
                ->where('status', 'refused')
                ->pluck('skill_id')
                ->toArray(),
            'user_zip' => ZipCode::where('zip_code', $this->user->zip_code)->first(),
        ];

        $offers = \App\Models\JobOffer::whereIn('id', $this->jobOfferIds)->get();
        
        // On traite tout dans une transaction avec retry pour éviter les verrous SQLite
        DB::transaction(function() use ($offers, $matchingService, $context) {
            foreach ($offers as $offer) {
                $matchingService->match($this->user, $offer, false, false, $context);
            }
        }, 5); // 5 tentatives en cas de verrou
    }
}
