<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserFact;
use Illuminate\Support\Facades\Log;

class AIProfileService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Traite un nouveau message dans la conversation de profil.
     * Renvoie la réponse de l'IA et les faits mis à jour.
     */
    public function chat(User $user, array $history): ?array
    {
        $context = $this->buildContext($user);
        
        $systemInstruction = "
        ## RÔLE : EXTRACTEUR DE DONNÉES (ZÉRO BLABLA)
        Tu es un moteur d'extraction froid et précis. Ton but : structurer le profil en 4 dimensions.

        ## DIMENSIONS (Catégories)
        1. **VALEURS** : Éthique, engagements (Ex: Écologie).
        2. **OBJECTIFS** : Ambitions, moteurs (Ex: Devenir chef).
        3. **SOFT_SKILLS** : Comportement (Ex: Autonomie).
        4. **PREFERENCES** : Environnement (Ex: Télétravail).

        ## RÈGLES DE GESTION DES FAITS (STRICT)
        - Chaque grande idée distincte (ex: écologie vs hiérarchie) DOIT être un FAIT SÉPARÉ (`add`).
        - Utilise `update` pour ENRICHIR un fait existant (ajouter des détails ou nuances au MÊME sujet), mais tu ne dois JAMAIS lui faire perdre son sens initial ni changer de sujet.
        - Lors d'un `update`, le nouveau texte DOIT conserver l'information précédente et y intégrer la nouvelle de façon naturelle. Ne supprime jamais le contexte précédent.
        - Ne fusionne jamais deux sujets complètement différents sous un même ID. Si le fait ID 1 parle d'\"Écologie\" et que l'utilisateur ajoute \"Je déteste la hiérarchie\", crée un NOUVEAU fait (`add`).
        - Utilise `delete` uniquement si l'utilisateur contredit ou retire explicitement un point.

        ## STYLE DE RÉPONSE
        - **INTERDIT** : Introductions ('C'est noté', 'Intéressant'), politesse, conclusions.
        - **OBLIGATOIRE** : Direct au but. Une question ou une remarque courte. Pas de phrases de transition (Max 15 mots).

        ## CONTEXTE ACTUEL
        {$context}

        ## RÈGLE D'EXTRACTION (CRUCIAL)
        - Ne ré-extrais JAMAIS une information déjà présente dans le CONTEXTE ACTUEL.
        - Analyse la dernière réponse de l'utilisateur pour y trouver des NOUVEAUTÉS.
        - Si l'utilisateur apporte une PRÉCISION sur un élément existant (même entreprise, même diplôme), utilise `update` avec l'ID correspondant.
        - N'utilise `add` QUE pour des éléments réellement nouveaux et absents du contexte.
        - Si tu hésites entre `add` et `update`, préfère `update` sur l'élément le plus proche.
        - Ne crée pas de doublons.

        ## RÈGLE SUR LES DATES
        - Format : YYYY-MM-DD ou null. Jamais de texte dans une date.
        - Si l'utilisateur donne juste une année, utilise YYYY-01-01.";

        $schema = [
            'type' => 'object',
            'properties' => [
                'reply' => [
                    'type' => 'string',
                    'description' => 'La question directe ou remarque courte (Max 15 mots).'
                ],
                'facts' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'category' => ['type' => 'string', 'enum' => ['VALEURS', 'OBJECTIFS', 'SOFT_SKILLS', 'PREFERENCES']],
                            'content' => ['type' => 'string']
                        ],
                        'required' => ['action', 'category', 'content']
                    ]
                ],
                'experiences' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'company' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'employment_type' => ['type' => 'string'],
                            'location' => ['type' => 'string'],
                            'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'end_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'is_current' => ['type' => 'boolean']
                        ],
                        'required' => ['action']
                    ]
                ],
                'educations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'school' => ['type' => 'string'],
                            'degree' => ['type' => 'string'],
                            'field' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'graduation_year' => ['type' => 'integer'],
                            'grade' => ['type' => 'string']
                        ],
                        'required' => ['action']
                    ]
                ],
                'projects' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'url' => ['type' => ['string', 'null']],
                            'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'end_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'is_ongoing' => ['type' => 'boolean']
                        ],
                        'required' => ['action']
                    ]
                ],
                'certifications' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'name' => ['type' => 'string'],
                            'issuing_organization' => ['type' => 'string'],
                            'issue_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'expiration_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'credential_id' => ['type' => 'string'],
                            'credential_url' => ['type' => 'string']
                        ],
                        'required' => ['action']
                    ]
                ],
                'volunteer_experiences' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'organization' => ['type' => 'string'],
                            'role' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'start_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'end_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                            'is_current' => ['type' => 'boolean']
                        ],
                        'required' => ['action']
                    ]
                ],
                'interests' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => ['integer', 'null']],
                            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete']],
                            'name' => ['type' => 'string']
                        ],
                        'required' => ['action']
                    ]
                ],
                'user_updates' => [
                    'type' => 'object',
                    'properties' => [
                        'birth_date' => ['type' => ['string', 'null'], 'format' => 'date'],
                        'links' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'label' => ['type' => 'string'],
                                    'url' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'required' => ['reply']
        ];

        // Conversion de l'historique au format Gemini (user/model)
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        return $this->gemini->chat($messages, $systemInstruction, $schema);
    }

    protected function buildContext(User $user): string
    {
        $skills = $user->skills->pluck('name')->implode(', ');
        $birthDate = $user->birth_date ? $user->birth_date->format('d/m/Y') : '? (Date de naissance manquante)';
        
        $context = "CANDIDAT: {$user->name}
NÉ LE: {$birthDate}
COMPÉTENCES: {$skills}";
        $experiences = $user->experiences->map(function($e) {
            $status = $e->status === 'draft' ? '[EN ATTENTE] ' : '';
            $dates = "(" . ($e->start_date?->format('Y') ?? '?') . " - " . ($e->is_current ? 'Aujourd\'hui' : ($e->end_date?->format('Y') ?? '?')) . ")";
            $desc = $e->description ?: 'DESCRIPTION MANQUANTE';
            return "- [ID: {$e->id}] {$status}{$e->title} chez {$e->company} {$dates} : {$desc}";
        })->implode("\n");
        
        $educations = $user->educations->map(function($e) {
            $status = $e->status === 'draft' ? '[EN ATTENTE] ' : '';
            $desc = $e->description ?: 'DESCRIPTION MANQUANTE';
            return "- [ID: {$e->id}] {$status}{$e->degree} à {$e->school} ({$e->graduation_year}) : {$desc}";
        })->implode("\n");

        $facts = $user->facts()->orderBy('local_id')->get()->map(function($f) {
            $pendingStr = '';
            $content = $f->content;
            if ($f->proposed_action === 'update') {
                $pendingStr = ' [MAJ EN ATTENTE : ' . $f->proposed_content . ']';
            }
            if ($f->proposed_action === 'delete') $pendingStr = ' [SUPPRESSION EN ATTENTE]';
            
            return "[ID: {$f->local_id}] ({$f->category}) {$content}{$pendingStr}";
        })->implode("\n");

        $stats = $user->facts()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category')->toArray();
        $density = "VALEURS: " . ($stats['VALEURS'] ?? 0) . ", OBJECTIFS: " . ($stats['OBJECTIFS'] ?? 0) . ", SOFT_SKILLS: " . ($stats['SOFT_SKILLS'] ?? 0) . ", PREFERENCES: " . ($stats['PREFERENCES'] ?? 0);

        return "
        ### RÉSUMÉ DU PROFIL
        - Expériences : " . $user->experiences->count() . "
        - Formations : " . $user->educations->count() . "
        - Faits narratifs : " . $user->facts->count() . "

        ### COUCHE 1 : PARCOURS (DÉTAILS)
        Expériences :
        " . ($experiences ?: 'AUCUNE') . "
        Formations :
        " . ($educations ?: 'AUCUNE') . "
        Projets :
        " . ($user->projects->map(fn($p) => "- [ID: {$p->id}] {$p->name} : " . ($p->description ?: 'DESCRIPTION MANQUANTE'))->implode("\n") ?: 'AUCUN') . "
        Compétences : {$skills}
        
        ### COUCHE 2 : PERSONNALITÉ (FAITS NARRATIFS)
        " . ($facts ?: 'AUCUN FAIT POUR LE MOMENT') . "
        
        DENSITÉ ACTUELLE : {$density}
        ";
    }

    /**
     * Génère un message d'ouverture pour commencer la conversation.
     */
    public function generateOpeningMessage(User $user): string
    {
        return "Bonjour {$user->name}. Je suis prêt à structurer ton profil narratif. Par quoi souhaites-tu commencer : tes valeurs, tes objectifs de carrière ou tes soft skills ?";
    }
}
