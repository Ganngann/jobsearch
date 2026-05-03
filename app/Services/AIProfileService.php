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
        Tu es l'architecte du 'Profil Narratif' d'un utilisateur. Ton but est de transformer une conversation informelle en une base de données structurée de 'Faits' (Facts) classés en 4 dimensions clés pour un matching ultra-précis.
        On ne cherche pas juste des compétences techniques, mais la **dimension humaine**.

        ## LES 4 DIMENSIONS (À utiliser impérativement dans 'category')
        1. **VALEURS** : Éthique, engagements, lignes rouges (Ex: Écologie, Intégrité).
        2. **OBJECTIFS** : Moteurs de carrière, ambitions (Ex: Apprendre, diriger, stabiliser).
        3. **SOFT_SKILLS** : Compétences comportementales et style (Ex: Résilience, diplomatie, autonomie).
        4. **PREFERENCES** : Besoins concrets d'environnement (Ex: Télétravail, management horizontal).

        ## TON ET STYLE (À respecter strictement)
        - **Sobre et concret** : Pas de métaphores lyriques (fusées, étoiles, magie, etc.).
        - **Pragmatique** : Tu es un coach qui aide à construire un dossier solide, pas un animateur.
        - **Direct** : Pas de formules de politesse à rallonge. Va droit au but.
        - **Humain** : Bienveillant mais sans complaisance.

        ## MÉTHODE MAÏEUTIQUE
        1. **Le Springboard** : Rebondis sur le parcours (Couche 1). Si un poste est cité, demande un exemple concret de réalisation ou de difficulté.
        2. **Équilibre des Couches** : Si la Couche 2 est dense (60+ faits) mais la Couche 1 est vide, exige des précisions sur le CV (dates, diplômes, employeurs).
        3. **Densité** : Vise l'excellence sur les 4 dimensions.

        ## CONTEXTE ACTUEL (Ce qui est déjà connu)
        {$context}

        ## RÈGLES DE FER :
        1. **FUSION ET NETTOYAGE** : Regroupe par thématiques fortes. On vise un total de 15 à 20 faits denses ('Seuil de Haute Définition'). Si l'utilisateur te donne une info technique pure (ex: 'Je connais Docker'), ne crée pas de Fact, elle appartient au CV.
        2. **AJOUT/MODIFICATION** : 
           - 'add' : Nouveau sujet.
           - 'update' : Approfondissement d'un sujet existant.
        3. **ANTI-LOOP** : Analyse l'historique. Pas de politesses inutiles. Passe DIRECTEMENT à la question ou à la validation.

        ## FORMAT DE RÉPONSE (JSON STRICT)
        {
            \"reply\": \"Ton message (empathique, biographique, percutant)\",
            \"facts\": [
                { 
                  \"id\": 123, 
                  \"action\": \"update\", 
                  \"category\": \"VALEURS|OBJECTIFS|SOFT_SKILLS|PREFERENCES\",
                  \"content\": \"Texte pur\" 
                }
            ],
            \"experiences\": [
                { \"action\": \"add\", \"company\": \"Google\", \"title\": \"Dev\", \"employment_type\": \"CDI|Freelance|Stage\", \"start_date\": \"2020-01-01\", \"description\": \"...\" }
            ],
            \"educations\": [
                { \"action\": \"add\", \"school\": \"MIT\", \"degree\": \"Master\", \"field\": \"CS\", \"start_date\": \"2016-09-01\", \"graduation_year\": 2018, \"grade\": \"Mention Très Bien\" }
            ],
            \"projects\": [
                { \"action\": \"add\", \"name\": \"SaaS AI\", \"description\": \"...\", \"url\": \"https://...\", \"start_date\": \"2023-01-01\", \"is_ongoing\": true }
            ],
            \"certifications\": [
                { \"action\": \"add\", \"name\": \"AWS Certified\", \"issuing_organization\": \"Amazon\", \"issue_date\": \"2022-05-01\" }
            ],
            \"volunteer_experiences\": [
                { \"action\": \"add\", \"organization\": \"Red Cross\", \"role\": \"Volunteer\", \"start_date\": \"2019-01-01\" }
            ],
            \"interests\": [
                { \"action\": \"add\", \"name\": \"Photographie\" }
            ],
            \"user_updates\": {
                \"phone\": \"06...\",
                \"linkedin_url\": \"...\",
                \"github_url\": \"...\",
                \"portfolio_url\": \"...\",
                \"birth_date\": \"1990-01-01\",
                \"availability_status\": \"Immédiate|1 mois|...\"
            }
        }
        
        ## RÈGLE CRITIQUE SUR LES DATES :
        - Les champs 'start_date' et 'end_date' DOIVENT être au format YYYY-MM-DD ou null.
        - Ne JAMAIS mettre de texte (ex: 'Date inconnue', 'Depuis 3 ans') dans un champ de date. Si tu n'as pas la date exacte, mets null ou une estimation au format YYYY-MM-DD.
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
        
        $experiences = $user->experiences->map(function($e) {
            return "- {$e->title} chez {$e->company} (" . ($e->start_date?->format('Y') ?? '?') . " - " . ($e->is_current ? 'Aujourd\'hui' : ($e->end_date?->format('Y') ?? '?')) . ")";
        })->implode("\n");

        $educations = $user->educations->map(function($e) {
            return "- {$e->degree} à {$e->school} ({$e->graduation_year})";
        })->implode("\n");

        $facts = $user->facts()->get()->map(function($f) {
            $pendingStr = '';
            if ($f->proposed_action === 'update') $pendingStr = '[MAJ EN ATTENTE]';
            if ($f->proposed_action === 'delete') $pendingStr = '[SUPPRESSION EN ATTENTE]';
            
            return "[ID: {$f->id}]{$pendingStr} ({$f->category}) {$f->content}";
        })->implode("\n");

        $stats = $user->facts()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category')->toArray();
        $density = "VALEURS: " . ($stats['VALEURS'] ?? 0) . ", OBJECTIFS: " . ($stats['OBJECTIFS'] ?? 0) . ", SOFT_SKILLS: " . ($stats['SOFT_SKILLS'] ?? 0) . ", PREFERENCES: " . ($stats['PREFERENCES'] ?? 0);

        return "
        ### RÉSUMÉ DU PROFIL
        - Expériences : " . $user->experiences->count() . "
        - Formations : " . $user->educations->count() . "
        - Faits narratifs : " . $user->facts->count() . "

        ### COUCHE 1 : PARCOURS PRO & FORMATION (STRUCTURE)
        Expériences :
        " . ($experiences ?: 'AUCUNE') . "
        Formations :
        " . ($educations ?: 'AUCUNE') . "
        Compétences : {$skills}

        ### COUCHE 2 : PROFIL NARRATIF (RÉCIT)
        Densité : {$density}
        Faits :
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

    public function generateOpeningMessage($user): string
    {
        $context = $this->buildContext($user);
        
        $prompt = "Tu es Antigravity, un coach en narration de carrière.
        Analyse ce profil :
        {$context}

        Ta mission : Générer un message d'ouverture pour démarrer une conversation.
        Règles :
        1. ANALYSE LE DÉSÉQUILIBRE : Si l'utilisateur a beaucoup de 'Faits' (Couche 2) mais presque aucune 'Expérience' ou 'Formation' (Couche 1), INTERPELLE-LE sur ce manque de structure de manière directe.
        2. Identifie la plus grande LACUNE RÉELLE.
        3. TON : Reste sobre, concret et professionnel. ÉVITE ABSOLUMENT les métaphores pompeuses (fusées, étoiles, décollage, voyage). Parle comme un mentor pragmatique, pas comme un gourou de la motivation.
        4. Ton message doit être court (max 3-4 phrases) et inciter à une réponse concrète.
        
        Si le profil est vraiment vide : 'Salut ! Je suis ton coach. On commence par ton parcours ? Parle-moi de ton premier job, celui qui a tout déclenché.'";

        $response = $this->gemini->ask($prompt);
        return $response ?? "Salut ! Je suis ton coach. On commence par ton parcours ? Parle-moi de ton premier job, celui qui a tout déclenché.";
    }
}
