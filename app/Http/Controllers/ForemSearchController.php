<?php

namespace App\Http\Controllers;

use App\Services\ForemApiService;
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
        // (Forem a besoin du libelle dans le payload filtresCodifies)
        $baseFilterData = $this->foremApi->getFilterCounts(['keywords' => $keywords]);
        $guidMap = [];
        foreach ($baseFilterData['criteresParFiltres'] ?? [] as $section) {
            foreach ($section['criteres'] as $c) {
                $guidMap[$c['guid']] = [
                    'libelle' => $c['libelle'],
                    'nom' => $section['nom'],
                    'section_libelle' => $section['libelle']
                ];
            }
        }

        // 2. Construire les payloads filtres et filtresCodifies
        $filtresCodifies = [];
        $filtresGrouped = [];
        $userFilters = $request->input('filters', []);

        foreach ($userFilters as $sectionNom => $guids) {
            $sectionGroup = [
                'nom' => $sectionNom,
                'libelle' => '',
                'criteres' => []
            ];

            foreach ($guids as $guid) {
                if (isset($guidMap[$guid])) {
                    $info = $guidMap[$guid];
                    $sectionGroup['libelle'] = $info['section_libelle'];
                    $sectionGroup['criteres'][] = [
                        'guid' => $guid,
                        'libelle' => $info['libelle']
                    ];
                    
                    $filtresCodifies[] = [
                        'guid' => $guid,
                        'libelle' => $info['libelle'],
                        'nom' => $sectionNom
                    ];
                }
            }

            if (!empty($sectionGroup['criteres'])) {
                $filtresGrouped[] = $sectionGroup;
            }
        }

        $criteria = [
            'keywords' => $keywords,
            'filters' => $filtresCodifies,
            'filtres_grouped' => $filtresGrouped
        ];

        // 3. Exécuter la recherche réelle
        $results = $this->foremApi->searchJobs($criteria, $page, $rows);
        
        // 4. Récupérer les compteurs mis à jour pour la sidebar
        $updatedFilterData = $this->foremApi->getFilterCounts($criteria);

        return view('forem.search', [
            'offers' => $results['offres'] ?? [],
            'total' => $results['nombreTotalOffres'] ?? 0,
            'filterData' => $updatedFilterData['criteresParFiltres'] ?? [],
            'query' => $query,
            'page' => $page,
            'rows' => $rows,
            'activeFilters' => $userFilters
        ]);
    }
}
