<?php

namespace App\Services;

use App\Models\User;
use App\Models\JobOffer;

class JobMatcherService
{
    /**
     * Calcule un score de compatibilité basé uniquement sur les données normalisées.
     */
    public function calculateHardScore(User $user, JobOffer $job): array
    {
        $details = [
            'skills' => $this->matchSkills($user, $job),
            'languages' => $this->matchLanguages($user, $job),
            'permits' => $this->matchPermits($user, $job),
            'location' => $this->matchLocation($user, $job),
        ];

        // Pondération : Skills (50%), Languages (20%), Permits (10%), Location (20%)
        $totalScore = ($details['skills']['score'] * 0.5) + 
                     ($details['languages']['score'] * 0.2) + 
                     ($details['permits']['score'] * 0.1) + 
                     ($details['location']['score'] * 0.2);

        return [
            'total_score' => round($totalScore),
            'details' => $details
        ];
    }

    protected function matchSkills(User $user, JobOffer $job): array
    {
        $jobSkills = $job->skills;
        if ($jobSkills->isEmpty()) {
            return ['score' => 100, 'matched' => [], 'missing' => []];
        }

        $userSkillIds = $user->skills->pluck('id')->toArray();
        $matched = [];
        $missing = [];
        $requiredMissingCount = 0;

        foreach ($jobSkills as $skill) {
            $skillData = ['id' => $skill->id, 'label' => $skill->label];
            if (in_array($skill->id, $userSkillIds)) {
                $matched[] = $skillData;
            } else {
                $missing[] = $skillData;
                if ($skill->pivot->is_required) {
                    $requiredMissingCount++;
                }
            }
        }

        $baseScore = (count($matched) / $jobSkills->count()) * 100;
        
        // Pénalité si des compétences REQUISES manquent
        if ($requiredMissingCount > 0) {
            $baseScore = $baseScore * 0.7; // On réduit de 30% s'il manque de l'indispensable
        }

        return [
            'score' => $baseScore,
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    protected function matchLanguages(User $user, JobOffer $job): array
    {
        $jobLangs = $job->languages;
        if ($jobLangs->isEmpty()) {
            return ['score' => 100, 'matched' => [], 'missing' => []];
        }

        $userLangs = $user->languages->keyBy('id');
        $matched = [];
        $missing = [];

        foreach ($jobLangs as $lang) {
            $langData = ['id' => $lang->id, 'label' => $lang->label];
            if ($userLangs->has($lang->id)) {
                $userLevel = $userLangs->get($lang->id)->pivot->level;
                $jobLevel = $lang->pivot->level;
                
                $matched[] = $langData;
            } else {
                $missing[] = $langData;
            }
        }

        return [
            'score' => (count($matched) / $jobLangs->count()) * 100,
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    protected function matchPermits(User $user, JobOffer $job): array
    {
        $jobPermits = $job->permits;
        if ($jobPermits->isEmpty()) {
            return ['score' => 100, 'matched' => [], 'missing' => []];
        }

        $userPermitIds = $user->permits->pluck('id')->toArray();
        $matched = [];
        $missing = [];

        foreach ($jobPermits as $permit) {
            $permitData = ['id' => $permit->id, 'label' => $permit->label];
            if (in_array($permit->id, $userPermitIds)) {
                $matched[] = $permitData;
            } else {
                $missing[] = $permitData;
            }
        }

        return [
            'score' => (count($matched) / $jobPermits->count()) * 100,
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    protected function matchLocation(User $user, JobOffer $job): array
    {
        // Si l'utilisateur n'a pas défini de zone de mobilité, on met 50% par défaut
        if (!$user->zip_code) {
            return ['score' => 50, 'message' => 'Zone de mobilité non définie'];
        }

        // TODO: Intégrer un calcul de distance réel via API ou table de distances
        // Pour l'instant on simule un match si même code postal ou si job à distance
        $jobZip = $this->extractZip($job->location);
        
        if ($jobZip === $user->zip_code) {
            return ['score' => 100, 'message' => 'Dans votre zone'];
        }

        return ['score' => 70, 'message' => 'À vérifier (distance)'];
    }

    protected function extractZip(?string $location): ?string
    {
        if (!$location) return null;
        preg_match('/\d{4}/', $location, $matches);
        return $matches[0] ?? null;
    }
}
