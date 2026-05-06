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
     */
    public function chat(User $user, array $history): ?array
    {
        $context = $this->buildContext($user);
        $systemInstruction = $this->getSystemInstructions($user, $context);
        
        $schema = [
            'type' => 'object',
            'properties' => [
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
                // ... autres schémas (experiences, etc.) conservés à l'identique ...
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
                'languages' => [
                    'type' => 'array',
                    'description' => 'Uniquement si l\'utilisateur mentionne ses compétences linguistiques.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string', 'description' => 'Ex: Anglais, Français, Allemand'],
                            'level' => ['type' => 'string', 'description' => 'Maternelle, Courant, Intermédiaire, Débutant']
                        ],
                        'required' => ['label', 'level']
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
                ],
                'reply' => [
                    'type' => 'string',
                    'description' => 'On change de sujet et on parle d\'autre chose.'
                ],
            ],
            'required' => ['reply']
        ];

        // Test : On ne garde que les 2 derniers messages (La question de l'IA + la réponse de l'utilisateur)
        $messages = [];
        $lastTwo = array_slice($history, -2);
        foreach ($lastTwo as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        return $this->gemini->chat($messages, $systemInstruction, $schema);
    }

    /**
     * Génère un message d'ouverture intelligent en utilisant la logique de chat standard.
     */
    public function generateOpeningMessage(User $user): array
    {
        // On simule un premier message utilisateur "virtuel" pour lancer la machine
        $virtualHistory = [
            ['role' => 'user', 'content' => 'Analyse mon profil et commence la conversation en identifiant un trou biographique ou une saturation.']
        ];

        return $this->chat($user, $virtualHistory) ?? ['reply' => "Bonjour {$user->name}. Je suis prêt à explorer ton parcours."];
    }

    /**
     * Centralisation des instructions système.
     */
    protected function getSystemInstructions(User $user, string $context): string
    {
        $factCount = $user->facts()->count();
        $consolidationInstruction = "";
        
        if ($factCount > 50) {
            $consolidationInstruction = "\n⚠️ MODE RÉDACTEUR EN CHEF : ALERTE SATURATION ({$factCount} faits).
            1. INTERDICTION FORMELLE d'écraser un fait par un sujet différent via 'update'.
            2. Pour chaque nouvelle info, tu DOIS obligatoirement LIBÉRER de la place : identifie deux faits similaires, FUSIONNE-LES en envoyant une action 'update' (le texte combiné) et une action 'delete'.
            3. Une fois la place libérée dans la même réponse, tu peux faire ton 'add'.
            4. Si tu ne trouves rien à fusionner, ne sauvegarde pas la nouvelle info, mais mentionne que le profil est saturé.
            5. Si le profil est saturé (> 20 faits), ton but prioritaire est de FUSIONNER les thématiques existantes pour descendre sous les 15 faits.\n";
        }

        $importInstruction = "";
        // On vérifie si le premier message ressemble à un import de CV
        $firstMessage = collect($user->profileMessages()->where('session_id', session('profile_builder_session'))->orderBy('created_at')->first())->get('content', '');
        if (str_contains($firstMessage, 'Voici le contenu de mon CV')) {
            $importInstruction = "\n🚀 MODE IMPORT EXHAUSTIF ACTIVÉ :
            Tu viens de recevoir un CV. Ton objectif est d'extraire un MAXIMUM d'informations structurées en UNE SEULE FOIS.
            - Ne te contente pas des titres : crée des 'experiences' détaillées.
            - Transforme chaque info pertinente en 'narrative_facts' (faits marquants, anecdotes, valeurs).
            - Identifie TOUS les projets, certifications, langues et intérêts.
            - Sois extrêmement méticuleux. Ne laisse rien de côté.\n";
        }

        // Bilan de progression pour le Coach
        $catCounts = $user->facts()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category')->toArray();
        $missingCategories = [];
        foreach(['VALEURS', 'OBJECTIFS', 'SOFT_SKILLS', 'PREFERENCES'] as $cat) {
            if(($catCounts[$cat] ?? 0) < 5) $missingCategories[] = str_replace('_', ' ', $cat);
        }
        $expCount = $user->experiences()->count();
        $eduCount = $user->educations()->count();
        $journeyCount = $expCount + $eduCount;
        $priorityCat = !empty($missingCategories) ? $missingCategories[0] : null;
        
        $statusReport = "\n📊 BILAN DE TON PROFIL (Score actuel : " . round($user->facts()->count() / 20 * 100) . "%) :
        - Récit Narratif : " . $user->facts()->count() . "/20 faits.
        - Parcours Pro : " . $journeyCount . " éléments. " . ($journeyCount < 3 ? "⚠️ Besoin de plus de détails sur ton parcours." : "✅ Parcours solide.") . "\n";

        if ($priorityCat) {
            $currentInCat = $catCounts[str_replace(' ', '_', $priorityCat)] ?? 0;
            $statusReport .= "🎯 OBJECTIF IMMÉDIAT : Creuser la section '" . $priorityCat . "' (on en a " . $currentInCat . "/5). C'est là que tu peux gagner le plus de points de richesse !\n";
        } else {
            $statusReport .= "✨ OBJECTIF : Ton profil est déjà très riche. Cherche maintenant la petite bête, les anecdotes insolites ou les tournants de vie inattendus.\n";
        }

        if ($user->profileMessages()->where('session_id', session('profile_builder_session'))->count() <= 1) {
            $statusReport .= "\n👉 INSTRUCTION DÉBUT DE SESSION : 
            Salue l'utilisateur et annonce-lui directement cet 'OBJECTIF IMMÉDIAT' pour lancer la discussion.\n";
        }

        return <<<EOT


PROFIL ACTUEL : 
{$context}

Tu es un biographe narratif expert en recrutement. Ton but est de reconstruire TOUTE la vie du candidat à travers une conversation profonde et fluide.

RÈGLES CRUCIALES POUR LES ACTIONS (JSON) :
1. ACTION 'ADD' : Utilise TOUJOURS 'add' pour toute nouvelle information extraite d'un CV ou mentionnée par l'utilisateur qui n'est PAS déjà listée dans le PROFIL ACTUEL ci-dessus.
2. ACTION 'UPDATE' : N'utilise 'update' QUE si tu modifies un élément possédant déjà un 'id' numérique dans le PROFIL ACTUEL. Tu DOIS alors fournir cet ID exact.
3. Ne devine JAMAIS d'ID. Si tu as un doute, utilise 'add'.

{$importInstruction}
{$consolidationInstruction}
RÈGLES D'INTERVIEW (Ton âme) :
1. PRIORITÉ ABSOLUE : Si une expérience ou une formation affiche `[DESCRIPTION MANQUANTE]`, ta mission PRIORITAIRE est de poser une question pour obtenir des détails sur les missions et réalisations de ce poste.
2. DYNAMISME & COHÉRENCE : Change de sujet pour explorer un "trou". Si tu extrais une info dans cette réponse (JSON), ta question ('reply') NE DOIT PAS porter sur ce sujet. Tu viens de l'apprendre, passe à la suite !
3. Cherche l'humain, le "pourquoi", les tournants de vie et les anecdotes.
4. Pose une seule question à la fois, percutante et courte (Max 20 mots).
5. Identifie les "vides" (trous chronologiques, sections non abordées, descriptions incomplètes, etc) et tire le fil du récit. Ne pose jamais de question sur ce qui est déjà dans le PROFIL ACTUEL.

RÈGLES D'ARCHIVAGE (Ta rigueur) :
1. CONSOLIDATION : Si une info renforce ou nuance un fait déjà présent, utilise `update` sur l'ID existant au lieu de `add`.
2. PAS DE DOUBLONS : Un fait = Une idée unique.

Queles infos pourraient manquer pour un recruteur? concnentre toi sur les trouss dans la biographie. pose des questions sur les choses que tu ne sais pas!
{$statusReport}

EOT;
    }

    protected function buildContext(User $user): string
    {
        $context = [
            'identity' => [
                'name' => $user->name,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? 'Unknown',
            ],
            'skills' => $user->validatedSkills->pluck('label')->toArray(),
            'experiences' => $user->experiences->map(fn($e) => array_filter([
                'id' => $e->id,
                'status' => $e->status !== 'validated' ? $e->status : null,
                'proposed_action' => $e->proposed_action,
                'title' => $e->title,
                'company' => $e->company,
                'location' => $e->location,
                'dates' => ($e->start_date?->format('Y') ?? '?') . " - " . ($e->is_current ? 'Present' : ($e->end_date?->format('Y') ?? '?')),
                'description' => $e->description ?: '[DESCRIPTION MANQUANTE]',
            ], fn($v) => !is_null($v))),
            'educations' => $user->educations->map(fn($e) => array_filter([
                'id' => $e->id,
                'status' => $e->status !== 'validated' ? $e->status : null,
                'school' => $e->school,
                'degree' => $e->degree,
                'graduation_year' => $e->graduation_year,
                'description' => $e->description ?: '[DESCRIPTION MANQUANTE]',
            ], fn($v) => !is_null($v))),
            'projects' => $user->projects->map(fn($p) => array_filter([
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
            ])),
            'certifications' => $user->certifications->map(fn($c) => array_filter([
                'id' => $c->id,
                'name' => $c->name,
                'organization' => $c->issuing_organization,
                'date' => $c->issue_date?->format('Y-m-d'),
            ])),
            'volunteer' => $user->volunteerExperiences->map(fn($v) => array_filter([
                'id' => $v->id,
                'organization' => $v->organization,
                'role' => $v->role,
                'description' => $v->description,
            ])),
            'interests' => $user->interests->map(fn($i) => ['id' => $i->id, 'name' => $i->name]),
            'languages' => $user->languages->map(fn($l) => ['label' => $l->label, 'level' => $l->pivot->level]),
            'narrative_facts' => $user->facts()->orderBy('local_id')->get()->map(fn($f) => array_filter([
                'id' => $f->local_id,
                'status' => $f->status !== 'validated' ? $f->status : null,
                'category' => $f->category,
                'content' => $f->content,
                'proposed_action' => $f->proposed_action,
                'proposed_content' => $f->proposed_content,
            ], fn($v) => !is_null($v))),
            'stats' => [
                'facts_count' => $user->facts->count(),
                'density' => $user->facts()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category')->toArray()
            ]
        ];

        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Traite les changements suggérés par l'IA (Faits, Expériences, etc.)
     */
    public function processAIChanges(User $user, array $aiResponse, $sessionId = null)
    {
        // 1. Traiter les faits suggérés (narrative_facts)
        $factUpdates = [];
        foreach ($aiResponse['facts'] ?? [] as $factData) {
            $rawId = $factData['id'] ?? null;
            $cleanId = $rawId ? preg_replace('/[^0-9]/', '', (string)$rawId) : null;
            $action = $factData['action'] ?? ($cleanId ? 'update' : 'add');

            if ($action === 'delete' && $cleanId) {
                $fact = $user->facts()->where('local_id', $cleanId)->first();
                if ($fact) $fact->update(['proposed_action' => 'delete']);
            } elseif ($action === 'update' && $cleanId) {
                if (!isset($factUpdates[$cleanId])) {
                    $factUpdates[$cleanId] = $factData;
                } else {
                    $factUpdates[$cleanId]['content'] .= " " . ($factData['content'] ?? '');
                }
            } else {
                if (!empty($factData['content'])) {
                    $user->facts()->create([
                        'session_id' => $sessionId,
                        'content' => $factData['content'],
                        'category' => $factData['category'] ?? 'VALEURS',
                        'status' => 'draft',
                        'proposed_action' => 'add',
                        'confidence_score' => 1.0
                    ]);
                }
            }
        }

        foreach ($factUpdates as $cleanId => $factData) {
            $fact = $user->facts()->where('local_id', $cleanId)->first();
            if ($fact) {
                $newContent = trim($factData['content'] ?? '');
                $newCategory = $factData['category'] ?? $fact->category;
                if ($fact->content !== $newContent || $fact->category !== $newCategory) {
                    $fact->update([
                        'proposed_content' => $newContent,
                        'proposed_category' => $newCategory,
                        'proposed_action' => 'update'
                    ]);
                }
            }
        }

        // 2. Traiter les expériences suggérées
        foreach ($aiResponse['experiences'] ?? [] as $expData) {
            $action = $expData['action'] ?? 'add';
            if ($action === 'add') {
                $user->experiences()->create([
                    'company' => $expData['company'] ?? '?',
                    'title' => $expData['title'] ?? '?',
                    'company_logo' => $expData['company_logo'] ?? null,
                    'employment_type' => $expData['employment_type'] ?? null,
                    'description' => $expData['description'] ?? null,
                    'location' => $expData['location'] ?? null,
                    'start_date' => $this->sanitizeDate($expData['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($expData['end_date'] ?? null),
                    'is_current' => $expData['is_current'] ?? false,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'update' && isset($expData['id'])) {
                $exp = $user->experiences()->find($expData['id']);
                if ($exp) {
                    $newData = array_filter([
                        'company' => $expData['company'] ?? null,
                        'title' => $expData['title'] ?? null,
                        'description' => $expData['description'] ?? null,
                        'location' => $expData['location'] ?? null,
                        'start_date' => isset($expData['start_date']) ? $this->sanitizeDate($expData['start_date']) : null,
                        'end_date' => isset($expData['end_date']) ? $this->sanitizeDate($expData['end_date']) : null,
                        'is_current' => $expData['is_current'] ?? null,
                    ], fn($v) => !is_null($v));
                    
                    if (!empty($newData)) {
                        $exp->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                    }
                }
            } elseif ($action === 'delete' && isset($expData['id'])) {
                $exp = $user->experiences()->find($expData['id']);
                if ($exp) $exp->update(['proposed_action' => 'delete']);
            }
        }

        // 3. Traiter les formations suggérées
        foreach ($aiResponse['educations'] ?? [] as $eduData) {
            $action = $eduData['action'] ?? 'add';
            if ($action === 'add') {
                $user->educations()->create([
                    'school' => $eduData['school'] ?? '?',
                    'degree' => $eduData['degree'] ?? '?',
                    'field' => $eduData['field'] ?? null,
                    'start_date' => $this->sanitizeDate($eduData['start_date'] ?? null),
                    'graduation_year' => $this->sanitizeYear($eduData['graduation_year'] ?? null),
                    'description' => $eduData['description'] ?? null,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'update' && isset($eduData['id'])) {
                $edu = $user->educations()->find($eduData['id']);
                if ($edu) {
                    $newData = array_filter([
                        'school' => $eduData['school'] ?? null,
                        'degree' => $eduData['degree'] ?? null,
                        'description' => $eduData['description'] ?? null,
                        'graduation_year' => $this->sanitizeYear($eduData['graduation_year'] ?? null),
                    ], fn($v) => !is_null($v));
                    
                    if (!empty($newData)) {
                        $edu->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                    }
                }
            } elseif ($action === 'delete' && isset($eduData['id'])) {
                $edu = $user->educations()->find($eduData['id']);
                if ($edu) $edu->update(['proposed_action' => 'delete']);
            }
        }

        // 4. Traiter le bénévolat suggéré (volunteer_experiences)
        foreach ($aiResponse['volunteer_experiences'] ?? [] as $data) {
            $action = $data['action'] ?? 'add';
            if ($action === 'add') {
                $user->volunteerExperiences()->create([
                    'organization' => $data['organization'] ?? '?',
                    'role' => $data['role'] ?? '?',
                    'description' => $data['description'] ?? null,
                    'start_date' => $this->sanitizeDate($data['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($data['end_date'] ?? null),
                    'is_current' => $data['is_current'] ?? false,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'update' && isset($data['id'])) {
                $item = $user->volunteerExperiences()->find($data['id']);
                if ($item) {
                    $newData = array_filter($data, fn($k) => in_array($k, ['organization', 'role', 'description', 'start_date', 'end_date', 'is_current']), ARRAY_FILTER_USE_KEY);
                    $item->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                }
            } elseif ($action === 'delete' && isset($data['id'])) {
                $item = $user->volunteerExperiences()->find($data['id']);
                if ($item) $item->update(['proposed_action' => 'delete']);
            }
        }

        // 5. Traiter les projets suggérés
        foreach ($aiResponse['projects'] ?? [] as $data) {
            $action = $data['action'] ?? 'add';
            if ($action === 'add') {
                $user->projects()->create([
                    'name' => $data['name'] ?? '?',
                    'description' => $data['description'] ?? null,
                    'url' => $data['url'] ?? null,
                    'start_date' => $this->sanitizeDate($data['start_date'] ?? null),
                    'end_date' => $this->sanitizeDate($data['end_date'] ?? null),
                    'is_ongoing' => $data['is_ongoing'] ?? false,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'update' && isset($data['id'])) {
                $item = $user->projects()->find($data['id']);
                if ($item) {
                    $newData = array_filter($data, fn($k) => in_array($k, ['name', 'description', 'url', 'start_date', 'end_date', 'is_ongoing']), ARRAY_FILTER_USE_KEY);
                    $item->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                }
            } elseif ($action === 'delete' && isset($data['id'])) {
                $item = $user->projects()->find($data['id']);
                if ($item) $item->update(['proposed_action' => 'delete']);
            }
        }

        // 6. Traiter les certifications suggérées
        foreach ($aiResponse['certifications'] ?? [] as $data) {
            $action = $data['action'] ?? 'add';
            if ($action === 'add') {
                $user->certifications()->create([
                    'name' => $data['name'] ?? '?',
                    'issuing_organization' => $data['issuing_organization'] ?? '?',
                    'issue_date' => $this->sanitizeDate($data['issue_date'] ?? null),
                    'expiration_date' => $this->sanitizeDate($data['expiration_date'] ?? null),
                    'credential_id' => $data['credential_id'] ?? null,
                    'credential_url' => $data['credential_url'] ?? null,
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'update' && isset($data['id'])) {
                $item = $user->certifications()->find($data['id']);
                if ($item) {
                    $newData = array_filter($data, fn($k) => in_array($k, ['name', 'issuing_organization', 'issue_date', 'expiration_date', 'credential_id', 'credential_url']), ARRAY_FILTER_USE_KEY);
                    $item->update(['proposed_data' => $newData, 'proposed_action' => 'update']);
                }
            } elseif ($action === 'delete' && isset($data['id'])) {
                $item = $user->certifications()->find($data['id']);
                if ($item) $item->update(['proposed_action' => 'delete']);
            }
        }

        // 7. Traiter les centres d'intérêt suggérés
        foreach ($aiResponse['interests'] ?? [] as $data) {
            $action = $data['action'] ?? 'add';
            if ($action === 'add' && !empty($data['name'])) {
                $user->interests()->create([
                    'name' => $data['name'],
                    'status' => 'draft',
                    'proposed_action' => 'add'
                ]);
            } elseif ($action === 'delete' && isset($data['id'])) {
                $item = $user->interests()->find($data['id']);
                if ($item) $item->update(['proposed_action' => 'delete']);
            }
        }

        // 8. Traiter les langues suggérées
        foreach ($aiResponse['languages'] ?? [] as $data) {
            $action = $data['action'] ?? 'add';
            if (!empty($data['label'])) {
                $lang = \App\Models\Language::where('label', $data['label'])->first();
                
                if ($action === 'delete' && $lang) {
                    $user->languages()->detach($lang->id);
                } elseif ($action !== 'delete') {
                    $lang = $lang ?: \App\Models\Language::create(['label' => $data['label']]);
                    $user->languages()->syncWithoutDetaching([$lang->id => ['level' => $data['level'] ?? 'Débutant']]);
                }
            }
        }

        // 9. Mises à jour profil (Headline, etc)
        if (!empty($aiResponse['user_updates'])) {
            $updates = array_filter($aiResponse['user_updates']);
            if (isset($updates['birth_date'])) $updates['birth_date'] = $this->sanitizeDate($updates['birth_date']);
            if (!empty($updates)) {
                $user->update($updates);
            }
        }
    }

    /**
     * Tente de parser une date et renvoie le format Y-m-d ou la valeur par défaut.
     */
    private function sanitizeDate($date, $default = null)
    {
        if (!$date) return $default;
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Extrait un entier d'une année potentiellement sous forme de texte.
     */
    private function sanitizeYear($year)
    {
        if (!$year) return null;
        $clean = preg_replace('/[^0-9]/', '', (string)$year);
        return $clean ? (int)$clean : null;
    }
}
