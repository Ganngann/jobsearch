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
    protected $signature = 'forem:sync {--pages=1 : Nombre de pages} {--rows=10 : Offres par page}';
    protected $description = 'Synchronise les offres depuis l\'API réelle du Forem';

    protected $foremApi;

    public function __construct(ForemApiService $foremApi)
    {
        parent::__construct();
        $this->foremApi = $foremApi;
    }

    public function handle()
    {
        $this->info('Début de la synchronisation réelle...');

        // 1. Offres (Par pages)
        $pages = (int) $this->option('pages');
        $rows = (int) $this->option('rows');

        for ($p = 1; $p <= $pages; $p++) {
            $this->comment("Traitement de la page {$p}/{$pages}...");
            $searchResults = $this->foremApi->searchJobs($p, $rows);
            
            // Correction de la clé ici : offreEmploiResumees
            $results = $searchResults['offreEmploiResumees'] ?? [];
            
            if (empty($results)) {
                $this->warn("Aucun résultat sur la page {$p}. Clés trouvées : " . implode(', ', array_keys($searchResults)));
                break;
            }

            foreach ($results as $item) {
                $jobId = $item['id'];
                
                $this->line("  Récupération de l'offre #{$jobId} : {$item['titre']}...");
                
                $jobData = $this->foremApi->getJobDetail($jobId);
                
                if ($jobData) {
                    $this->saveJobOffer($jobData);
                    $this->info("    --> Sauvegardée !");
                }

                // PARSIMONIE : Pause aléatoire entre 1.5 et 3 secondes pour être discret
                usleep(rand(1500000, 3000000));
            }
        }

        $this->info('Synchronisation terminée !');
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

            // Metier (Extraction sécurisée)
            $metierLabel = $jobData['metier'] ?? 'Métier non spécifié';
            $metierCode = $jobData['experience'][0]['code'] ?? null;
            $metier = Metier::updateOrCreate(['label' => $metierLabel], ['code' => $metierCode]);

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
                    'is_postulable' => $jobData['isPostulable'] ?? false,
                    'start_date' => $this->parseDate($jobData['positionDateInfo']['startDate'] ?? null),
                    'published_at' => now(),
                    'expires_at' => $this->parseDate($jobData['dateFinDiffusion'] ?? null),
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

    protected function parseDate($dateString)
    {
        if (!$dateString) return null;
        try {
            // L'API semble renvoyer de l'ISO8601 (ex: 2026-06-11T00:00:00+02:00)
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }
}
