<?php

namespace App\Http\Controllers;

use App\Services\ForemApiService;
use App\Models\JobOffer;
use Illuminate\Http\Request;

class ForemSearchController extends Controller
{
    protected $foremApi;

    public function __construct(ForemApiService $foremApi)
    {
        $this->foremApi = $foremApi;
    }

    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $page = $request->input('page', 1);
        $rows = 20;
        $keywords = $query ? explode(' ', $query) : [];

        // 1. Récupérer tous les critères possibles pour mapper les GUIDs aux Libellés
        $baseFilterData = $this->foremApi->getFilterCounts(['keywords' => $keywords]);
        $guidMap = [];
        foreach ($baseFilterData['criteresParFiltres'] ?? [] as $section) {
            foreach ($section['criteres'] as $c) {
                $guidMap[$c['guid']] = [
                    'libelle' => $c['libelle'],
                    'section_nom' => $section['nom'],
                    'section_libelle' => $section['libelle']
                ];
            }
        }

        // 2. Construire le payload filtres (groupé par section)
        $filtresGrouped = [];
        $userFilters = $request->input('filters', []);

        foreach ($userFilters as $sectionNom => $guids) {
            $sectionGroup = [
                'nom' => $sectionNom,
                'libelle' => $sectionNom,
                'criteres' => []
            ];

            foreach ($guids as $guid) {
                if (isset($guidMap[$guid])) {
                    $info = $guidMap[$guid];
                    $sectionGroup['libelle'] = $info['section_libelle'];
                    $sectionGroup['nom'] = $info['section_nom'];
                    
                    $sectionGroup['criteres'][] = [
                        'guid' => $guid,
                        'libelle' => $info['libelle']
                    ];
                }
            }

            if (!empty($sectionGroup['criteres'])) {
                $filtresGrouped[] = $sectionGroup;
            }
        }

        $criteria = [
            'keywords' => $keywords,
            'filters' => [],
            'filtres_grouped' => $filtresGrouped
        ];

        // 3. Exécuter la recherche réelle
        $results = $this->foremApi->searchJobs($criteria, $page, $rows);
        $offers = $results['offres'] ?? [];

        // 4. Vérifier quels jobs sont déjà en DB
        $existingJobIds = [];
        if (!empty($offers)) {
            $foremIds = collect($offers)->pluck('idOffreEmploi')->filter()->toArray();
            $existingJobIds = JobOffer::whereIn('forem_id', $foremIds)->pluck('forem_id')->toArray();
        }
        
        // 5. Récupérer les compteurs mis à jour pour la sidebar
        $updatedFilterData = $this->foremApi->getFilterCounts($criteria);
        
        $finalFilterData = $updatedFilterData['criteresParFiltres'] ?? [];
        if (empty($finalFilterData)) {
            $finalFilterData = $baseFilterData['criteresParFiltres'] ?? [];
        }

        return view('forem.search', [
            'offers' => $offers,
            'total' => $results['nombreTotalOffres'] ?? 0,
            'filterData' => $finalFilterData,
            'query' => $query,
            'page' => $page,
            'rows' => $rows,
            'activeFilters' => $userFilters,
            'existingJobIds' => $existingJobIds
        ]);
    }
}
