<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobOffer;
use App\Models\Language;
use App\Models\Metier;
use App\Models\Permit;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;

class JobOfferService
{
    protected $foremApi;

    public function __construct(ForemApiService $foremApi)
    {
        $this->foremApi = $foremApi;
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
                    'published_at' => now(),
                    'start_date' => isset($item['debut']) ? $this->parseDate($item['debut']) : null,
                    'expires_at' => isset($item['fin']) ? $this->parseDate($item['fin']) : null,
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
        });
    }

    /**
     * Récupère les détails complets d'une offre et met à jour la DB.
     */
    public function syncFullDetails(JobOffer $jobOffer)
    {
        \Illuminate\Support\Facades\Log::info("Début du Lazy Loading pour l'offre #{$jobOffer->forem_id}");
        
        $jobData = $this->foremApi->getJobDetail($jobOffer->forem_id);
        
        if (!$jobData) {
            \Illuminate\Support\Facades\Log::error("Échec de récupération des détails pour l'offre #{$jobOffer->forem_id}");
            return false;
        }

        \Illuminate\Support\Facades\Log::info("Détails reçus pour #{$jobOffer->forem_id}, mise à jour de la DB...");

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
            $metierCode = $jobData['experience'][0]['code'] ?? null;
            $metier = Metier::updateOrCreate(['label' => $metierLabel], ['code' => $metierCode]);

            // Update Job Offer
            $jobOffer->update([
                'forem_ref' => $jobData['numero'],
                'title' => $jobData['titreOffre'],
                'metier_id' => $metier->id,
                'employer_id' => $employer->id,
                'description' => $jobData['descriptionJob'] ?? '',
                'contract_type' => $jobData['typeContrat'] ?? 'N/A',
                'working_regime' => $jobData['regimeTravail'] ?? 'N/A',
                'working_regime_detail' => $jobData['regimeTravailPrecision'] ?? null,
                'working_hours' => $this->sanitizeNumeric($jobData['shift']['hours'] ?? null),
                'shift_period' => $jobData['shift']['shiftPeriod'] ?? null,
                'base_salary' => $this->sanitizeNumeric($jobData['benefits']['basePay'] ?? null),
                'benefits_comments' => $jobData['benefits']['comments'] ?? null,
                'nombre_postes' => $jobData['nombrePostes'] ?? 1,
                'location' => $jobData['lieuxTravail'][0] ?? null,
                'locations_json' => $jobData['lieuxTravail'] ?? [],
                'contact_name' => ($jobData['howToApply']['prefferedGivenName'] ?? '') . ' ' . ($jobData['howToApply']['familyName'] ?? ''),
                'contact_email' => $jobData['howToApply']['email'] ?? null,
                'apply_instructions' => $jobData['howToApply']['comments'] ?? null,
                'is_postulable' => $jobData['isPostulable'] ?? false,
                'start_date' => $this->parseDate($jobData['positionDateInfo']['startDate'] ?? null),
                'expires_at' => $this->parseDate($jobData['dateFinDiffusion'] ?? null),
                'raw_data' => $jobData,
                'is_detailed' => true,
            ]);

            // Skills
            $allSkills = array_merge(
                array_map(fn($s) => array_merge($s, ['type' => 'hard']), $jobData['competencies'] ?? []),
                array_map(fn($s) => array_merge($s, ['type' => 'soft']), $jobData['softSkills'] ?? [])
            );

            foreach ($allSkills as $sData) {
                $skill = Skill::updateOrCreate(
                    ['code' => $sData['code'] ?? $sData['libelle']],
                    ['label' => $sData['libelle'], 'type' => $sData['type']]
                );
                $jobOffer->skills()->syncWithoutDetaching([
                    $skill->id => ['is_required' => $sData['required'] ?? false]
                ]);
            }

            // Languages
            foreach ($jobData['langues'] ?? [] as $lData) {
                $lang = Language::updateOrCreate(
                    ['code' => $lData['code'] ?? $lData['libelle']],
                    ['label' => $lData['libelle']]
                );
                $jobOffer->languages()->syncWithoutDetaching([
                    $lang->id => ['level' => $lData['experience'] ?? null, 'is_required' => true]
                ]);
            }

            // Permits
            foreach ($jobData['permisConduire'] ?? [] as $pData) {
                $permit = Permit::updateOrCreate(
                    ['code' => $pData['code'] ?? $pData['valeur']],
                    ['label' => $pData['libelle'], 'value' => $pData['valeur'] ?? 'B']
                );
                $jobOffer->permits()->syncWithoutDetaching([$permit->id => ['is_required' => true]]);
            }

            return true;
        });
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
