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
                'reply' => [
                    'type' => 'string',
                    'description' => 'La question directe ou remarque courte (Max 20 mots).'
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
                ]
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
        
        if ($factCount > 20) {
            $consolidationInstruction = "\n⚠️ MODE RÉDACTEUR EN CHEF : ALERTE SATURATION ({$factCount} faits).
            1. INTERDICTION FORMELLE d'écraser un fait par un sujet différent via 'update'.
            2. Pour chaque nouvelle info, tu DOIS obligatoirement LIBÉRER de la place : identifie deux faits similaires, FUSIONNE-LES en envoyant une action 'update' (le texte combiné) et une action 'delete'.
            3. Une fois la place libérée dans la même réponse, tu peux faire ton 'add'.
            4. Si tu ne trouves rien à fusionner, ne sauvegarde pas la nouvelle info, mais mentionne que le profil est saturé.\n";
        }

        return <<<EOT
Tu es un biographe narratif. Ton but est de reconstruire TOUTE la vie du candidat à travers une conversation profonde et fluide.
{$consolidationInstruction}
RÈGLES D'INTERVIEW (Ton âme) :
1. DYNAMISME : Ne t'attarde pas trop sur un seul sujet. Dès que tu as l'essentiel d'une expérience ou d'un fait, change radicalement de sujet pour explorer un "trou" ou une autre facette du profil (formation, passions, bénévolat, vie associative).
2. Cherche l'humain, le "pourquoi", les tournants de vie et les anecdotes.
3. Identifie les "vides" (trous chronologiques, sections non abordées) et tire le fil du récit.
4. Pose une seule question à la fois, percutante et courte (Max 20 mots).

RÈGLES D'ARCHIVAGE (Ta rigueur) :
1. CONSOLIDATION : Si une info renforce ou nuance un fait déjà présent, utilise `update` sur l'ID existant au lieu de `add`.
2. PAS DE DOUBLONS : Un fait = Une idée unique.
3. Si le profil est saturé (> 20 faits), ton but prioritaire est de FUSIONNER les thématiques existantes pour descendre sous les 15 faits.

PROFIL ACTUEL : 
{$context}
EOT;
    }

    protected function buildContext(User $user): string
    {
        $context = [
            'identity' => [
                'name' => $user->name,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? 'Unknown',
            ],
            'skills' => $user->skills->pluck('name')->toArray(),
            'experiences' => $user->experiences->map(fn($e) => array_filter([
                'id' => $e->id,
                'status' => $e->status,
                'proposed_action' => $e->proposed_action,
                'title' => $e->title,
                'company' => $e->company,
                'location' => $e->location,
                'dates' => ($e->start_date?->format('Y') ?? '?') . " - " . ($e->is_current ? 'Present' : ($e->end_date?->format('Y') ?? '?')),
                'description' => $e->description,
            ])),
            'educations' => $user->educations->map(fn($e) => array_filter([
                'id' => $e->id,
                'status' => $e->status,
                'school' => $e->school,
                'degree' => $e->degree,
                'graduation_year' => $e->graduation_year,
                'description' => $e->description,
            ])),
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
                'category' => $f->category,
                'content' => $f->content,
                'proposed_action' => $f->proposed_action,
                'proposed_content' => $f->proposed_content,
            ])),
            'stats' => [
                'facts_count' => $user->facts->count(),
                'density' => $user->facts()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category')->toArray()
            ]
        ];

        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
