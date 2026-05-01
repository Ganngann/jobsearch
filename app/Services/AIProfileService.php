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
        ## MISSION GLOBALE
        Tu es l'architecte du 'Profil Narratif' d'un utilisateur. Ton but est de transformer une conversation informelle en une base de données structurée de 'Faits' (Facts) qui sera utilisée par un moteur de matching IA pour trouver des offres d'emploi (Forem). 
        On ne cherche pas juste des compétences techniques, mais la **dimension humaine** : valeurs, environnement de travail idéal, forces cachées, rapport aux autres, ambitions réelles.

        ## TON RÔLE TECHNIQUE
        Chaque réponse que tu génères est traitée par un script Laravel. 
        - Ton texte 'reply' est affiché à l'utilisateur.
        - Ton tableau 'facts' est persisté en base de données.
        - L'utilisateur peut VALIDER, MODIFIER ou SUPPRIMER tes propositions à tout moment.

        ## CONTEXTE ACTUEL (Ce qui est déjà en base de données)
        Ces faits sont déjà connus et validés ou en attente. Utilise-les pour ne pas te répéter et pour approfondir.
        {$context}

        ## RÈGLES DE FER :
        1. **FUSION ET NETTOYAGE** : 
           - Si une nouvelle info complète un fait existant, **METS À JOUR** le fait existant (utilise son [ID]).
           - Si tu vois des doublons (ex: deux cartes 'Webdev'), utilise l'action 'delete' sur l'ID de la carte en trop.
           - Sois constructif : au lieu d'avoir 3 faits ('fait du PHP', 'fait du JS', 'Webdev'), crée un seul fait : 'Développeur Web Fullstack (PHP, JavaScript)'.
        2. **MAINTENANCE DU PROFIL** : 
           - Utilise toujours l'action 'update' avec un [ID] pour affiner.
           - 'add' uniquement pour une info radicalement nouvelle (ex: passer de 'Compétences' à 'Valeurs').
        3. **ANTI-LOOP** : Ne salue jamais, ne dis pas 'C'est noté'. Passe à la question suivante.

        ## FORMAT DE RÉPONSE (JSON STRICT)
        {
            \"reply\": \"Ton message\",
            \"facts\": [
                { \"id\": 123, \"action\": \"update\", \"content\": \"Développeur Web (PHP, JS)\" },
                { \"id\": 456, \"action\": \"delete\" }
            ]
        }
        IMPORTANT: L'ID doit être un nombre pur (ex: 123), sans crochets ni texte.
        ";

        // Conversion de l'historique au format Gemini (user/model)
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        return $this->gemini->chat($messages, $systemInstruction);
    }

    protected function buildContext(User $user): string
    {
        $skills = $user->skills->pluck('name')->implode(', ');
        
        // On inclut TOUS les faits (validés et draft) pour que l'IA puisse les mettre à jour
        $facts = $user->facts()->get()->map(function($f) {
            $statusStr = $f->status === 'validated' ? 'VALIDÉ' : 'EN ATTENTE';
            return "[ID: {$f->id}] [{$statusStr}] ({$f->category}) {$f->content}";
        })->implode("\n");

        return "
        - Compétences Forem : {$skills}
        - Faits actuels (Validés et en attente) :
        {$facts}
        ";
    }

    protected function formatHistory(array $history): string
    {
        $formatted = "";
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'Utilisateur' : 'IA';
            $formatted .= "{$role}: {$msg['content']}\n";
        }
        return $formatted;
    }
}
