<?php

namespace App\Console\Commands;

use App\Models\Benefit;
use App\Models\Employer;
use App\Models\Language;
use App\Models\Metier;
use App\Models\Permit;
use App\Models\Sector;
use App\Models\Skill;
use App\Models\Source;
use App\Models\Study;
use App\Models\JobOffer;
use App\Services\ForemApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForemSyncCommand extends Command
{
    protected $signature = 'forem:sync';
    protected $description = 'Synchronise les taxonomies et les offres d\'emploi depuis l\'API Forem';

    protected $foremApi;

    public function __construct(ForemApiService $foremApi)
    {
        parent::__construct();
        $this->foremApi = $foremApi;
    }

    public function handle()
    {
        $this->info('Début de la synchronisation...');

        $this->syncTaxonomies();
        $this->syncJobs();

        $this->info('Synchronisation terminée !');
    }

    protected function syncTaxonomies()
    {
        $this->comment('Synchronisation des taxonomies...');
        $taxonomies = $this->foremApi->getTaxonomies();

        // 1. Métiers
        $metiers = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Metier')['criteres'] ?? [];
        foreach ($metiers as $m) {
            Metier::updateOrCreate(
                ['label' => $m['libelle']],
                ['guid' => $m['guid'] ?? null, 'code' => 'N/A'] // Le code ROME vient du Detail API
            );
        }

        // 2. Langues
        $languages = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Language')['criteres'] ?? [];
        foreach ($languages as $l) {
            Language::updateOrCreate(
                ['label' => $l['libelle']],
                ['code' => $l['guid'] ?? $l['libelle']] // À affiner
            );
        }

        // 3. Permis
        $permits = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Drivers License')['criteres'] ?? [];
        foreach ($permits as $p) {
            Permit::updateOrCreate(
                ['label' => $p['libelle']],
                ['code' => $p['guid'] ?? $p['libelle'], 'value' => $this->extractPermitValue($p['libelle'])]
            );
        }

        // 4. Secteurs (Activite)
        $sectors = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Activite')['criteres'] ?? [];
        foreach ($sectors as $s) {
            Sector::updateOrCreate(['label' => $s['libelle']]);
        }

        // 5. Sources
        $sources = collect($taxonomies['criteresParFiltreCodifies'] ?? [])->firstWhere('nom', 'Source')['criteres'] ?? [];
        foreach ($sources as $s) {
            Source::updateOrCreate(
                ['code' => $s['id']],
                ['label' => $s['libelle']]
            );
        }

        // 6. Avantages (Benefits)
        $benefits = collect($taxonomies['criteresParFiltreCodifies'] ?? [])->firstWhere('nom', 'Avantage')['criteres'] ?? [];
        foreach ($benefits as $b) {
            Benefit::updateOrCreate(
                ['code' => $b['id']],
                ['label' => $b['libelle']]
            );
        }

        $this->info('Taxonomies synchronisées.');
    }

    protected function syncJobs()
    {
        $this->comment('Synchronisation des offres (simulée)...');
        
        // Simuler la récupération d'une offre via le Detail API
        $jobData = $this->foremApi->getJobDetail(1878785);

        if (empty($jobData)) {
            $this->error('Aucune donnée d\'offre trouvée.');
            return;
        }

        DB::transaction(function () use ($jobData) {
            // 1. Employer
            $employer = Employer::updateOrCreate(
                ['label' => $jobData['nomEmployeur']],
                [
                    'logo_base64' => $jobData['logoEmployeur'] ?? null,
                    'logo_mime_type' => $jobData['logoMimeType'] ?? null,
                    'description' => $jobData['descriptionEmployeur'] ?? null,
                ]
            );

            // 2. Metier (Update code ROME)
            $metier = Metier::updateOrCreate(
                ['label' => $jobData['metier']],
                ['code' => $jobData['experience'][0]['code'] ?? 'N/A']
            );

            // 3. Job Offer
            $jobOffer = JobOffer::updateOrCreate(
                ['forem_id' => $jobData['idOffreEmploi']],
                [
                    'forem_ref' => $jobData['numero'],
                    'title' => $jobData['titreOffre'],
                    'metier_id' => $metier->id,
                    'employer_id' => $employer->id,
                    'description' => $jobData['descriptionJob'],
                    'contract_type' => $jobData['typeContrat'],
                    'working_regime' => $jobData['regimeTravail'],
                    'working_regime_detail' => $jobData['regimeTravailPrecision'],
                    'working_hours' => $jobData['shift']['hours'] ?? null,
                    'shift_period' => $jobData['shift']['shiftPeriod'] ?? null,
                    'base_salary' => $jobData['benefits']['basePay'] ?? null,
                    'benefits_comments' => $jobData['benefits']['comments'] ?? null,
                    'nombre_postes' => $jobData['nombrePostes'] ?? 1,
                    'location' => $jobData['lieuxTravail'][0] ?? null,
                    'locations_json' => $jobData['lieuxTravail'],
                    'contact_name' => ($jobData['howToApply']['prefferedGivenName'] ?? '') . ' ' . ($jobData['howToApply']['familyName'] ?? ''),
                    'contact_email' => $jobData['howToApply']['email'] ?? null,
                    'contact_phone' => $jobData['howToApply']['telephone'] ?? null, // Hypothetical
                    'apply_instructions' => $jobData['howToApply']['comments'] ?? null,
                    'is_postulable' => str_contains($jobData['howToApply']['comments'] ?? '', 'Forem'), // Simplifié
                    'start_date' => $this->parseFrenchDate($jobData['positionDateInfo']['startDate'] ?? null),
                    'published_at' => now(), // À améliorer avec Search API
                    'expires_at' => $this->parseFrenchDate($jobData['dateFinDiffusion'] ?? null),
                    'raw_data' => $jobData,
                ]
            );

            // 4. Skills (Competencies + SoftSkills)
            $allSkills = array_merge(
                array_map(fn($s) => array_merge($s, ['type' => 'hard']), $jobData['competencies'] ?? []),
                array_map(fn($s) => array_merge($s, ['type' => 'soft']), $jobData['softSkills'] ?? [])
            );

            foreach ($allSkills as $sData) {
                $skill = Skill::updateOrCreate(
                    ['code' => $sData['code']],
                    ['label' => $sData['libelle'], 'type' => $sData['type']]
                );
                $jobOffer->skills()->syncWithoutDetaching([
                    $skill->id => ['is_required' => $sData['required'] ?? false]
                ]);
            }

            // 5. Languages
            foreach ($jobData['langues'] ?? [] as $lData) {
                $lang = Language::updateOrCreate(
                    ['code' => $lData['code']],
                    ['label' => $lData['libelle']]
                );
                $jobOffer->languages()->syncWithoutDetaching([
                    $lang->id => [
                        'level' => $lData['experience'] ?? null,
                        'is_required' => true // Souvent requis par défaut
                    ]
                ]);
            }

            // 6. Permits
            foreach ($jobData['permisConduire'] ?? [] as $pData) {
                $permit = Permit::updateOrCreate(
                    ['code' => $pData['code']],
                    ['label' => $pData['libelle'], 'value' => $pData['valeur'] ?? 'B']
                );
                $jobOffer->permits()->syncWithoutDetaching([
                    $permit->id => ['is_required' => true]
                ]);
            }

            // 7. Studies
            foreach ($jobData['etudes'] ?? [] as $eData) {
                $study = Study::updateOrCreate(['label' => $eData['libelle']]);
                $jobOffer->studies()->syncWithoutDetaching([$study->id]);
            }
        });

        $this->info('Offres synchronisées.');
    }

    protected function extractPermitValue($label)
    {
        if (preg_match('/Permis ([\w+]+)/i', $label, $matches)) {
            return $matches[1];
        }
        return 'B';
    }

    protected function parseFrenchDate($dateString)
    {
        if (!$dateString) return null;
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
