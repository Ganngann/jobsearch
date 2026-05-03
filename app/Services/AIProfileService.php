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
        - **Extrême brièveté** : Pas d'introduction, pas de transitions ('Je comprends', 'C'est intéressant'), pas de conclusion.
        - **Zéro politesse** : Pas de 'Bonjour', 'Merci', 'Cordialement'.
        - **Questionnement direct** : Pose ta question ou fais ta remarque immédiatement. Une phrase suffit souvent.
        - **Pas de blabla** : Si tu as extrait une info, confirme-la brièvement ou pose la question suivante.
        - **Sobriété** : Pas de métaphores. Langage technique et factuel.

        ## MÉTHODE MAÏEUTIQUE
        1. **Priorité Structurelle** : Si une expérience (Couche 1) affiche des '?' pour les dates (l'année suffit) ou 'DESCRIPTION MANQUANTE', tu as INTERDICTION de poser une question narrative. Demande d'abord les faits (Dates, Lieux, Missions).
        2. **Le Springboard** : Uniquement si la structure est complète. Rebondis sur le parcours pour extraire des Soft Skills.
        3. **Équilibre des Couches** : Vise l'excellence sur les 4 dimensions.

        ## CONTEXTE ACTUEL (Ce qui est déjà connu)
        {$context}

        ## RÈGLES DE FER :
        1. **PRIORITÉ : COMPLÉTION DES DONNÉES (COUCHE 1 & 2)** : Ta priorité absolue est de supprimer les 'DESCRIPTION MANQUANTE', les '?' dans les dates (l'année suffit largement), et les mentions 'AUCUNE/AUCUN'. Si tu vois un tel marqueur dans le contexte, tu DOIS poser une question directe pour le résoudre.
        2. **FUSION ET CONSOLIDATION (DENSITÉ)** : Regroupe les faits par thématiques. Si l'utilisateur apporte une précision sur un sujet déjà listé, utilise 'update' sur l'ID correspondant (Ex: [ID: 3] -> 'id': 3). Il est CRITIQUE d'utiliser l'ID réel présent dans le contexte (local_id). Ne crée pas de nouveau fait pour une simple précision. On vise 15 à 20 faits denses et uniques.
        3. **ANTI-LOOP & REBOND** : Il est strictement INTERDIT de dire 'J'ai bien noté' sans poser une question sur une lacune identifiée. Si tu viens d'extraire une info, passe immédiatement à la lacune suivante (ex: 'C'est noté pour X. Par contre, je n'ai aucune description pour ta formation Y, peux-tu m'en dire plus ?').
        4. **EXTRACTION JSON** : Toute information structurée (dates, diplômes, noms d'entreprises) doit être extraite dans les tableaux JSON correspondants.
        5. **ZERO PLACEHOLDER** : Ne propose jamais de contenu fictif ou générique. Si tu ne sais pas, demande.
        6. **PRÉCISION DES DATES** : Ne demande JAMAIS le jour exact d'une date (naissance ou expérience). L'année ou le mois/année suffisent largement. Si tu as déjà l'année, considère que la donnée n'est plus manquante (pas de '?').
        7. **PAS DE REDONDANCE** : Ne renvoie jamais un fait dans le JSON si son contenu est identique à celui déjà présent dans le contexte. Concentre-toi sur les nouveautés ou les corrections.
        8. **UNICITÉ DES ID** : Il est strictement INTERDIT de renvoyer plusieurs objets 'update' pour le même ID dans une seule réponse JSON. Si tu as plusieurs nouvelles informations concernant le même ID, tu DOIS les fusionner en un seul texte riche et cohérent dans le champ 'content'.
        9. **COHÉRENCE THÉMATIQUE** : Il est strictement INTERDIT de détourner un ID existant pour un sujet totalement différent. Si l'ID 5 parle de 'Management', n'utilise pas cet ID pour injecter une info sur le 'Permis B'. Utilise l'ID qui correspond au sujet ou crée un nouveau fait.
        10. **NETTOYAGE DES DOUBLONS** : Si tu identifies des faits redondants ou quasi-identiques dans le contexte, utilise 'update' sur l'ID le plus complet pour fusionner les informations, et utilise impérativement 'delete' sur les IDs superflus pour nettoyer le CV.

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
                \"availability_status\": \"Immédiate|1 mois|...\",
                \"links\": [
                    { \"label\": \"GitHub\", \"url\": \"https://github.com/...\" },
                    { \"label\": \"Behance\", \"url\": \"...\" }
                ]
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
        $birthDate = $user->birth_date ? $user->birth_date->format('d/m/Y') : '? (Date de naissance manquante)';
        
        $context = "CANDIDAT: {$user->name}
NÉ LE: {$birthDate}
COMPÉTENCES: {$skills}";
        $experiences = $user->experiences->map(function($e) {
            $dates = "(" . ($e->start_date?->format('Y') ?? '?') . " - " . ($e->is_current ? 'Aujourd\'hui' : ($e->end_date?->format('Y') ?? '?')) . ")";
            $desc = $e->description ?: 'DESCRIPTION MANQUANTE';
            return "- [ID: {$e->id}] {$e->title} chez {$e->company} {$dates} : {$desc}";
        })->implode("\n");

        $educations = $user->educations->map(function($e) {
            $desc = $e->description ?: 'DESCRIPTION MANQUANTE';
            return "- [ID: {$e->id}] {$e->degree} à {$e->school} ({$e->graduation_year}) : {$desc}";
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
        
        ### COUCHE 2 : FAITS NARRATIFS
        Densité : {$density}
        Faits :
        {$facts}
        
        ### AUTRES ÉLÉMENTS
        Liens : " . (collect($user->links)->map(fn($l) => "{$l['label']}: {$l['url']}")->implode(', ') ?: 'AUCUN') . "
        Certifications : " . ($user->certifications->pluck('name')->implode(', ') ?: 'AUCUNE') . "
        Intérêts : " . ($user->interests->pluck('name')->implode(', ') ?: 'AUCUN') . "
        Bénévolat : " . ($user->volunteerExperiences->pluck('role')->implode(', ') ?: 'AUCUN') . "
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
        
        $prompt = "Tu es un coach en narration de carrière.
        Analyse ce profil :
        {$context}

        Ta mission : Générer une question d'ouverture d'une brièveté extrême.
        Règles :
        1. AUCUNE POLITESSE : Pas de 'Bonjour', 'Salut', 'J'ai analysé votre profil'. 
        2. FOCUS GAPS : Si une expérience ou formation a des '?' (dates) ou 'DESCRIPTION MANQUANTE', pose la question uniquement sur ce point. 
           - Ex: 'Votre rôle de Gérant de boulangerie n'a pas de date de fin. Quand s'est-il terminé ?'
        3. SI TOUT EST OK : Pose une question sur une dimension manquante (Valeurs, Objectifs).
        4. LONGUEUR : 1 phrase, 2 maximum.
        ";
        
        $response = $this->gemini->ask($prompt);
        return $response ?? "Commençons par le début. Quel était votre tout premier poste ou diplôme ?";
    }
}
