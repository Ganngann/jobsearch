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
        1. **FUSION ET NETTOYAGE (SÉMANTIQUE)** : 
           - **'update'** : Utilise un [ID] uniquement si la nouvelle information concerne le MÊME SUJET que le fait existant (ex: ajouter une techno à une carte SKILL). 
           - **JAMAIS d'update pour changer de sujet** : Ne remplace pas une compétence technique par un trait de personnalité ou un goût personnel.
           - Si tu vois des doublons (ex: deux cartes 'Webdev'), utilise l'action 'delete' sur l'ID de la carte en trop.
        2. **AJOUT DE CONTENU** : 
           - **'add'** : Utilise cette action pour toute information qui traite d'un nouveau sujet ou qui n'a pas de lien direct avec les faits existants.
           - En cas de doute, préfère 'add' plutôt que d'écraser une information existante.
        3. **COMMANDES UTILISATEUR** : Si l'utilisateur te demande de \"rassembler\", \"nettoyer\" ou   \"réorganiser\" ses faits, tu DOIS impérativement utiliser les actions 'update' et 'delete' pour refléter ce changement dans la structure. Ne te contente pas de le dire dans le texte 'reply'.
        4. **ANTI-LOOP** : Ne salue jamais, ne dis pas 'C'est noté', 'Je vois', 'Parfait'. Ne répète pas ta question précédente si l'utilisateur a déjà eu la réponse. Analyse l'historique pour éviter de tourner en rond. Passe DIRECTEMENT à la question suivante ou à la validation des faits.
        5. **FLEXIBILITÉ** : Si l'utilisateur exprime une frustration ou remarque que tu te répètes, change radicalement d'approche ou propose un résumé global pour valider le profil.

        ## FORMAT DE RÉPONSE (JSON STRICT)
        {
            \"reply\": \"Ton message (bref, percutant, pas de blabla inutile)\",
            \"facts\": [
                { 
                  \"id\": 123, 
                  \"action\": \"update\", 
                  \"category\": \"CONTEXT|VALUE|CHALLENGE|SKILL\",
                  \"content\": \"Texte pur sans préfixe de catégorie\" 
                },
                { \"id\": 456, \"action\": \"delete\" }
            ]
        }
        IMPORTANT: Le champ 'category' est obligatoire pour les actions 'add' et fortement recommandé pour 'update'. N'écris JAMAIS la catégorie dans le texte 'content' (ex: pas de \"(VALUE) texte\").
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
            $pendingStr = '';
            if ($f->proposed_action === 'update') $pendingStr = '[MAJ EN ATTENTE]';
            if ($f->proposed_action === 'delete') $pendingStr = '[SUPPRESSION EN ATTENTE]';
            
            return "[ID: {$f->id}] [{$statusStr}]{$pendingStr} ({$f->category}) {$f->content}";
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
