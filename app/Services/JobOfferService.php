<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobOffer;
use App\Models\Language;
use App\Models\Metier;
use App\Models\Permit;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobOfferService
{
    protected $foremApi;
    protected $matchingService;

    public function __construct(ForemApiService $foremApi, MatchingService $matchingService)
    {
        $this->foremApi = $foremApi;
        $this->matchingService = $matchingService;
    }

    /**
     * Sauvegarde une offre à partir des données de recherche (résumé).
     * Riche en informations dans l'API du Forem.
     */
    public function saveBasicOffer(array $item)
    {
        return DB::transaction(function () use ($item) {
            // Employer
            $employer = null;
            if (isset($item['nomEmployeur'])) {
                $employer = Employer::updateOrCreate(
                    ['label' => $item['nomEmployeur']],
                    []
                );
            }

            // Smart Refresh: Si les dates changent, on invalide les détails pour forcer un re-scan complet
            $existingOffer = JobOffer::where('forem_id', $item['id'])->first();
            $isDetailed = $existingOffer ? $existingOffer->is_detailed : false;
            $newExpiresAt = isset($item['fin']) ? $this->parseDate($item['fin']) : null;
            
            if ($existingOffer && $existingOffer->expires_at && $newExpiresAt) {
                if ($existingOffer->expires_at->format('Y-m-d') !== $newExpiresAt->format('Y-m-d')) {
                    $isDetailed = false; // Date de fin modifiée -> On veut rafraîchir
                }
            }

            // Job Offer
            $jobOffer = JobOffer::updateOrCreate(
                ['forem_id' => $item['id']],
                [
                    'forem_ref' => $item['numero'] ?? null,
                    'title' => $item['titre'] ?? 'Sans titre',
                    'employer_id' => $employer?->id,
                    'location' => $item['lieuxTravail'][0] ?? null,
                    'locations_json' => $item['lieuxTravail'] ?? [],
                    'contract_type' => $item['typeContrat'] ?? 'N/A',
                    'working_regime' => $item['regimeTravail'] ?? 'N/A',
                    'nombre_postes' => $item['nombrePostes'] ?? 1,
                    'contact_email' => $item['email'] ?? null,
                    'is_postulable' => $item['isPostulable'] ?? false,
                    'start_date' => isset($item['debut']) ? $this->parseDate($item['debut']) : null,
                    'expires_at' => $newExpiresAt,
                    'last_seen_at' => now(),
                    'status' => 'active',
                    'is_detailed' => $isDetailed,
                ]
            );

            // Sectors (Si présents)
            if (isset($item['secteursActivite'])) {
                foreach ($item['secteursActivite'] as $sectorLabel) {
                    $sector = \App\Models\Sector::updateOrCreate(['label' => $sectorLabel]);
                    $jobOffer->sectors()->syncWithoutDetaching([$sector->id]);
                }
            }

            return $jobOffer;
        }, 5);
    }

    /**
     * Récupère les détails complets d'une offre et met à jour la DB.
     */
    public function syncFullDetails(JobOffer $jobOffer)
    {
        \Illuminate\Support\Facades\Log::debug("Début du Lazy Loading pour l'offre #{$jobOffer->forem_id}");
        
        $jobData = $this->foremApi->getJobDetail($jobOffer->forem_id);
        
        if ($jobData === null) {
            \Illuminate\Support\Facades\Log::warning("Offre #{$jobOffer->forem_id} introuvable sur le Forem. Marquage comme expirée.");
            $jobOffer->update(['status' => 'expired', 'is_detailed' => true]);
            return false;
        }

        if (empty($jobData)) {
            \Illuminate\Support\Facades\Log::error("Échec de récupération des détails pour l'offre #{$jobOffer->forem_id}");
            return false;
        }

        \Illuminate\Support\Facades\Log::debug("Détails reçus pour #{$jobOffer->forem_id}, mise à jour de la DB...");

        return DB::transaction(function () use ($jobOffer, $jobData) {
            // Employer
            $employer = Employer::updateOrCreate(
                ['label' => $jobData['nomEmployeur'] ?? 'Employeur inconnu'],
                [
                    'logo_base64' => $jobData['logoEmployeur'] ?? null,
                    'logo_mime_type' => $jobData['logoMimeType'] ?? null,
                    'description' => $jobData['descriptionEmployeur'] ?? null,
                ]
            );

            // Metier
            $metierLabel = $jobData['metier'] ?? 'Métier non spécifié';
            $metierCode = $jobData['experience'][0]['code'] ?? (Str::slug($metierLabel) ?: 'N/A');
            $metier = Metier::updateOrCreate(['label' => $metierLabel], ['code' => $metierCode]);

            // Construction de la description complète à partir de plusieurs champs Forem
            $descriptionParts = [
                $jobData['descriptionEmployeur'] ?? '',
                $jobData['descriptionJob'] ?? '',
                $jobData['descriptionComment'] ?? '',
                $jobData['commentaireGeneral'] ?? '',
                $jobData['benefitsComments'] ?? '',
            ];
            $fullDescription = implode("\n\n", array_filter($descriptionParts));

            // Content Hash pour l'IA (Titre + Description + Localisation)
            $contentToHash = ($jobData['title'] ?? $jobData['titreOffre'] ?? $jobOffer->title) . '|' . 
                             $fullDescription . '|' . 
                             ($jobData['lieuxTravail'][0] ?? '');
            $newHash = md5($contentToHash);

            // Invalidation des scores IA si le contenu a changé
            if ($jobOffer->content_hash && $jobOffer->content_hash !== $newHash) {
                $jobOffer->matches()->delete(); // On invalide les scores existants
            }

            // Update Job Offer
            $jobOffer->update([
                'forem_ref' => $jobData['numero'],
                'metier_id' => $metier->id,
                'employer_id' => $employer->id,
                'description' => $fullDescription,
                'title' => $jobData['title'] ?? $jobData['titreOffre'] ?? $jobOffer->title,
                'contract_type' => $jobData['contractType'] ?? $jobData['typeContrat'] ?? $jobOffer->contract_type,
                'working_regime' => $jobData['regimeTravail'] ?? $jobOffer->working_regime,
                'working_regime_detail' => $jobData['regimeTravailPrecision'] ?? null,
                'working_hours' => $this->sanitizeNumeric($jobData['shift']['hours'] ?? null),
                'shift_period' => $jobData['shift']['shiftPeriod'] ?? null,
                'base_salary' => $jobData['baseSalary'] ?? null,
                'benefits_comments' => $jobData['benefitsComments'] ?? null,
                'nombre_postes' => $jobData['nombrePostes'] ?? 1,
                'location' => $jobData['lieuxTravail'][0] ?? $jobOffer->location,
                'locations_json' => $jobData['lieuxTravail'] ?? [],
                'travel_info' => !empty($jobData['travel']) ? json_encode($jobData['travel']) : null,
                'contact_name' => ($jobData['howToApply']['prefferedGivenName'] ?? '') . ' ' . ($jobData['howToApply']['familyName'] ?? ''),
                'contact_email' => $jobData['howToApply']['email'] ?? null,
                'apply_instructions' => $jobData['howToApply']['comments'] ?? null,
                'apply_url' => $jobData['howToApply']['webAddress'] ?? null,
                'is_postulable' => $jobData['isPostulable'] ?? false,
                'start_date' => $this->parseDate($jobData['positionDateInfo']['startDate'] ?? null),
                'published_at' => $this->parseDate($jobData['dateDebutDiffusion'] ?? $jobData['positionDateInfo']['postedDate'] ?? null),
                'expires_at' => $this->parseDate($jobData['dateFinDiffusion'] ?? null),
                'is_detailed' => true,
                'detailed_at' => now(),
                'content_hash' => $newHash,
                'raw_data' => $jobData,
            ]);

            // Sync Relationships
            
            // Skills (Technical, Soft, Office)
            $allSkills = [];
            
            // Technical
            $techSkills = $jobData['competences'] ?? $jobData['competencies'] ?? [];
            foreach ($techSkills as $sData) {
                if (!isset($sData['libelle'])) continue;
                $slug = \Illuminate\Support\Str::slug($sData['libelle']);
                $skill = Skill::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $sData['libelle'], 
                        'code' => isset($sData['code']) && strlen($sData['code']) <= 10 ? $sData['code'] : $slug,
                        'type' => 'hard'
                    ]
                );
                $allSkills[$skill->id] = ['is_required' => $sData['required'] ?? true];
            }

            // Soft Skills
            foreach ($jobData['softSkills'] ?? [] as $sData) {
                if (!isset($sData['libelle'])) continue;
                $slug = Str::slug($sData['libelle']);
                $skill = Skill::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $sData['libelle'], 
                        'code' => isset($sData['code']) && strlen($sData['code']) <= 10 ? $sData['code'] : $slug,
                        'type' => 'soft'
                    ]
                );
                $allSkills[$skill->id] = ['is_required' => $sData['required'] ?? true];
            }

            // Office Skills
            foreach ($jobData['officeSkills'] ?? [] as $sData) {
                if (!isset($sData['libelle'])) continue;
                $slug = Str::slug($sData['libelle']);
                $skill = Skill::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $sData['libelle'], 
                        'code' => isset($sData['code']) && strlen($sData['code']) <= 10 ? $sData['code'] : $slug,
                        'type' => 'hard'
                    ]
                );
                $allSkills[$skill->id] = ['is_required' => $sData['required'] ?? true];
            }

            $jobOffer->skills()->sync($allSkills);

            // Languages
            foreach ($jobData['langues'] ?? [] as $lData) {
                if (!isset($lData['libelle'])) continue;
                $slug = Str::slug($lData['libelle']);
                $lang = Language::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $lData['libelle'],
                        'code' => isset($lData['code']) && strlen($lData['code']) <= 5 ? strtoupper($lData['code']) : strtoupper(substr($slug, 0, 3))
                    ]
                );
                $jobOffer->languages()->syncWithoutDetaching([
                    $lang->id => ['level' => $lData['experience'] ?? null, 'is_required' => true]
                ]);
            }

            // Permits
            foreach ($jobData['permisConduire'] ?? [] as $pData) {
                $label = $pData['libelle'] ?? $pData['valeur'] ?? null;
                if (!$label) continue;
                $slug = Permit::generateSlug($label);
                $permit = Permit::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'label' => $label, 
                        'code' => isset($pData['code']) && strlen($pData['code']) <= 5 ? $pData['code'] : strtoupper(substr($slug, 0, 3)),
                        'value' => $pData['valeur'] ?? 'B'
                    ]
                );
                $jobOffer->permits()->syncWithoutDetaching([$permit->id => ['is_required' => true]]);
            }

            // Experience
            foreach ($jobData['experience'] ?? [] as $eData) {
                if (!isset($eData['libelle'])) continue;
                
                $expMetier = Metier::updateOrCreate(
                    ['label' => $eData['libelle']],
                    ['code' => $eData['code'] ?? (Str::slug($eData['libelle']) ?: 'N/A')]
                );
                $jobOffer->requiredExperiences()->syncWithoutDetaching([
                    $expMetier->id => [
                        'is_required' => true,
                        'experience_label' => $eData['experience'] ?? null
                    ]
                ]);
            }

            // Etudes
            foreach ($jobData['etudes'] ?? [] as $stData) {
                if (!isset($stData['libelle'])) continue;

                $study = \App\Models\Study::updateOrCreate(['label' => $stData['libelle']]);
                $jobOffer->studies()->syncWithoutDetaching([$study->id]);
            }

            return true;
        }, 5);

        // APRÈS la transaction : Déclenchement du matching pour la cohorte d'utilisateurs concernés
        if ($jobOffer->metier_id) {
            $users = \App\Models\User::whereHas('preferredMetiers', function($q) use ($jobOffer) {
                $q->where('metiers.id', $jobOffer->metier_id);
            })->get();

            DB::transaction(function() use ($users, $jobOffer) {
                foreach ($users as $user) {
                    // On calcule au moins le Layer 1. L'IA suivra si pre_score >= 70.
                    $this->matchingService->match($user, $jobOffer);
                }
            }, 5);
        }

        return true;
    }

    protected function sanitizeNumeric($value)
    {
        if (!$value) return null;
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return is_numeric($value) ? (float) $value : null;
    }

    protected function parseDate($dateString)
    {
        if (!$dateString) return null;
        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
