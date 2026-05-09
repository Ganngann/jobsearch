<?php

namespace App\Services;

use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VectorService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Génère et sauvegarde le vecteur pour une offre d'emploi.
     */
    public function updateJobVector(JobOffer $job): bool
    {
        // On ne vectorise pas une offre dont le détail n'a pas été chargé.
        if (!$job->is_detailed) {
            Log::warning("Annulation de la vectorisation pour l'offre #{$job->id} : détails non chargés.");
            return false;
        }

        $text = $this->buildJobString($job);
        $vector = $this->gemini->embed($text, 'RETRIEVAL_DOCUMENT');

        if ($vector) {
            $normalized = $this->normalize($vector);
            
            try {
                // Tentative de sauvegarde avec une patience accrue (10 tentatives, 1s d'intervalle)
                // Total de 10 secondes de patience avant l'échec critique.
                return retry(10, function() use ($job, $normalized) {
                    return $job->update(['vector_embedding' => $normalized]);
                }, 1000); 
            } catch (\Exception $e) {
                $errorMsg = "ÉCHEC CRITIQUE : Impossible de sauvegarder le vecteur pour l'offre #{$job->id} après 10 tentatives. Arrêt du processus pour éviter tout gaspillage API. Erreur : " . $e->getMessage();
                Log::error($errorMsg);
                // On lance une exception pour stopper net le processus appelant
                throw new \RuntimeException($errorMsg);
            }
        }

        return false;
    }

    /**
     * Génère et sauvegarde le vecteur pour un utilisateur.
     */
    public function updateUserVector(User $user): bool
    {
        $text = $this->buildUserString($user);
        $vector = $this->gemini->forUser($user)->embed($text, 'RETRIEVAL_QUERY');

        if ($vector) {
            $normalized = $this->normalize($vector);
            
            try {
                return retry(5, function() use ($user, $normalized) {
                    return $user->update(['vector_embedding' => $normalized]);
                }, 100);
            } catch (\Exception $e) {
                Log::error("Impossible de sauvegarder le vecteur pour l'utilisateur #{$user->id} : " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Calcule la similitude cosinus entre deux vecteurs.
     * Puisque les vecteurs sont normalisés à l'insertion, le cosinus est égal au produit scalaire.
     */
    public function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $count = count($vec1);
        
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
        }
        
        return (float) $dotProduct;
    }

    /**
     * Calcule le score sémantique remappé (0-100) basé sur un seuil.
     */
    public function calculateSemanticScore(array $vec1, array $vec2): float
    {
        $cosine = $this->cosineSimilarity($vec1, $vec2);
        
        // Log anomalie si besoin
        if ($cosine > 1.2 || $cosine < -1.2) {
            Log::warning("Anomalie Cosinus : {$cosine}. Vérifiez la normalisation des vecteurs.");
        }

        /**
         * LOGIQUE DE SCORING SÉMANTIQUE (REVISITÉE)
         * ----------------------------------------
         * Les embeddings Gemini se situent souvent entre 0.4 et 0.85 pour des textes pertinents.
         * On utilise une échelle linéaire plus généreuse :
         * 0.3 (Bruit/Non pertinent) -> 0%
         * 0.6 (Minimum métier acceptable) -> 50%
         * 0.8 (Excellent match) -> 83%
         * 0.9 (Match parfait en pratique) -> 100%
         */
        $min = 0;
        $max = 1;
        
        $score = (($cosine - $min) / ($max - $min)) * 100;
        
        return (float) max(0, min(100, $score));
    }

    /**
     * Normalise un vecteur (L2 norm).
     */
    protected function normalize(array $vector): array
    {
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $vector)));
        
        if ($norm == 0) return $vector;
        
        return array_map(fn($x) => $x / $norm, $vector);
    }

    /**
     * Construit la chaîne de texte exhaustive pour une offre.
     */
    protected function buildJobString(JobOffer $job): string
    {
        $allSkills = $job->skills;
        $hardSkills = $allSkills->where('type', 'hard')->pluck('label')->implode(', ');
        $softSkills = $allSkills->where('type', 'soft')->pluck('label')->implode(', ');

        $languages = $job->languages->pluck('label')->implode(', ');
        $permits = $job->permits->pluck('label')->implode(', ');
        $sectors = $job->sectors->pluck('label')->implode(', ');
        $experiences = $job->requiredExperiences->pluck('label')->implode(', ');
        $studies = $job->studies->pluck('label')->implode(', ');

        $content = "Métier: {$job->metier?->label} | ";
        $content .= "Description: " . strip_tags($job->description) . " | ";
        $content .= "Compétences Techniques: {$hardSkills} | ";
        $content .= "Soft Skills: {$softSkills} | ";
        $content .= "Langues: {$languages} | ";
        $content .= "Permis: {$permits} | ";
        $content .= "Secteurs: {$sectors} | ";
        $content .= "Expériences: {$experiences} | ";
        $content .= "Études: {$studies} | ";
        $content .= "Employeur: " . strip_tags($job->employer?->description ?? '');

        return "title: {$job->title} | text: " . trim($content);
    }

    /**
     * Construit la chaîne de texte exhaustive pour un utilisateur.
     */
    protected function buildUserString(User $user): string
    {
        $allSkills = $user->validatedSkills;
        $hardSkills = $allSkills->where('type', 'hard')->pluck('label')->implode(', ');
        $softSkills = $allSkills->where('type', 'soft')->pluck('label')->implode(', ');

        $facts = $user->facts->pluck('content')->implode(' ');
        $experiences = $user->experiences->map(fn($e) => "{$e->title} chez {$e->company}: {$e->description}")->implode(' | ');
        $educations = $user->educations->map(fn($e) => "{$e->degree} à {$e->school}")->implode(' | ');
        $projects = $user->projects->pluck('name')->implode(', ');
        $certifications = $user->certifications->pluck('name')->implode(', ');
        $languages = $user->languages->pluck('label')->implode(', ');
        $interests = $user->interests->pluck('name')->implode(', ');

        $content = "Headline: {$user->headline} | ";
        $content .= "Aspirations: {$user->aspirations} | ";
        $content .= "Bio: {$user->profile_text} | ";
        $content .= "Compétences Techniques: {$hardSkills} | ";
        $content .= "Soft Skills: {$softSkills} | ";
        $content .= "Récits: {$facts} | ";
        $content .= "Parcours: {$experiences} | ";
        $content .= "Études: {$educations} | ";
        $content .= "Projets: {$projects} | ";
        $content .= "Certifs: {$certifications} | ";
        $content .= "Langues: {$languages} | ";
        $content .= "Loisirs: {$interests}";

        return "task: search result | query: " . trim($content);
    }
}
