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
    protected $signature = 'forem:sync {--pages=1 : Nombre de pages de recherche à parcourir} {--rows=10 : Nombre d\'offres par page}';
    protected $description = 'Synchronise les taxonomies et les offres d\'emploi depuis l\'API réelle du Forem';

    protected $foremApi;

    public function __construct(ForemApiService $foremApi)
    {
        parent::__construct();
        $this->foremApi = $foremApi;
    }

    public function handle()
    {
        $this->info('Début de la synchronisation réelle...');

        // 1. Taxonomies (Une fois au début)
        $this->syncTaxonomies();

        // 2. Offres (Par pages)
        $pages = (int) $this->option('pages');
        $rows = (int) $this->option('rows');

        for ($p = 1; $p <= $pages; $p++) {
            $this->comment("Traitement de la page {$p}/{$pages}...");
            $searchResults = $this->foremApi->searchJobs($p, $rows);
            
            $results = $searchResults['resultats'] ?? [];
            
            if (empty($results)) {
                $this->warn("Aucun résultat sur la page {$p}.");
                break;
            }

            foreach ($results as $item) {
                $jobId = $item['idOffreEmploi'] ?? $item['id'];
                
                // Vérifier si on a déjà l'offre (optionnel, on peut mettre à jour)
                // if (JobOffer::where('forem_id', $jobId)->exists()) continue;

                $this->line("  Récupération de l'offre #{$jobId}...");
                
                $jobData = $this->foremApi->getJobDetail($jobId);
                
                if ($jobData) {
                    $this->saveJobOffer($jobData);
                    $this->info("    --> Sauvegardée : {$jobData['titreOffre']}");
                }

                // PARSIMONIE : Pause aléatoire entre 1 et 2 secondes
                usleep(rand(1000000, 2000000));
            }
        }

        $this->info('Synchronisation terminée !');
    }

    protected function syncTaxonomies()
    {
        $this->comment('Synchronisation des taxonomies...');
        $taxonomies = $this->foremApi->getTaxonomies();

        if (empty($taxonomies)) return;

        // Métiers
        $metiers = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Metier')['criteres'] ?? [];
        foreach ($metiers as $m) {
            Metier::updateOrCreate(['label' => $m['libelle']], ['guid' => $m['guid'] ?? null]);
        }

        // Langues
        $languages = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Language')['criteres'] ?? [];
        foreach ($languages as $l) {
            Language::updateOrCreate(['label' => $l['libelle']], ['code' => $l['guid'] ?? $l['libelle']]);
        }

        // Permis
        $permits = collect($taxonomies['criteresParFiltres'] ?? [])->firstWhere('nom', 'Drivers License')['criteres'] ?? [];
        foreach ($permits as $p) {
            Permit::updateOrCreate(
                ['label' => $p['libelle']],
                ['code' => $p['guid'] ?? $p['libelle'], 'value' => $this->extractPermitValue($p['libelle'])]
            );
        }

        $this->info('Taxonomies de base synchronisées.');
    }

    protected function saveJobOffer(array $jobData)
    {
        DB::transaction(function () use ($jobData) {
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
            $metier = Metier::updateOrCreate(
                ['label' => $jobData['metier'] ?? 'Métier non spécifié'],
                ['code' => $jobData['experience'][0]['code'] ?? 'N/A']
            );

            // Job Offer
            $jobOffer = JobOffer::updateOrCreate(
                ['forem_id' => $jobData['idOffreEmploi']],
                [
                    'forem_ref' => $jobData['numero'],
                    'title' => $jobData['titreOffre'],
                    'metier_id' => $metier->id,
                    'employer_id' => $employer->id,
                    'description' => $jobData['descriptionJob'] ?? '',
                    'contract_type' => $jobData['typeContrat'] ?? 'N/A',
                    'working_regime' => $jobData['regimeTravail'] ?? 'N/A',
                    'working_regime_detail' => $jobData['regimeTravailPrecision'] ?? null,
                    'working_hours' => $jobData['shift']['hours'] ?? null,
                    'shift_period' => $jobData['shift']['shiftPeriod'] ?? null,
                    'base_salary' => $jobData['benefits']['basePay'] ?? null,
                    'benefits_comments' => $jobData['benefits']['comments'] ?? null,
                    'nombre_postes' => $jobData['nombrePostes'] ?? 1,
                    'location' => $jobData['lieuxTravail'][0] ?? null,
                    'locations_json' => $jobData['lieuxTravail'] ?? [],
                    'contact_name' => ($jobData['howToApply']['prefferedGivenName'] ?? '') . ' ' . ($jobData['howToApply']['familyName'] ?? ''),
                    'contact_email' => $jobData['howToApply']['email'] ?? null,
                    'apply_instructions' => $jobData['howToApply']['comments'] ?? null,
                    'is_postulable' => str_contains($jobData['howToApply']['comments'] ?? '', 'Forem'),
                    'start_date' => $this->parseFrenchDate($jobData['positionDateInfo']['startDate'] ?? null),
                    'published_at' => now(),
                    'expires_at' => $this->parseFrenchDate($jobData['dateFinDiffusion'] ?? null),
                    'raw_data' => $jobData,
                ]
            );

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
        });
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
