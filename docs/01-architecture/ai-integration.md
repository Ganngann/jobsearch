# Stratégie de Scoring & IA

## Modèle : Google Gemini 2.5 Flash

### Configuration
- **Temperature** : `0.2` (réponses déterministes et factuelles)
- **Max Output Tokens** : `1024`
- **Response MIME Type** : `application/json`
- **Langue de réponse** : Français obligatoire

### Barème de scoring
Le score final est calculé sur **100 points**, répartis comme suit :

| Critère | Poids | Description |
| :--- | :--- | :--- |
| Compétences techniques (hard skills) | 35% | Correspondance entre `user_skills` et `job_skill` (type hard) |
| Savoir-être (soft skills) | 15% | Correspondance entre `user_skills` et `job_skill` (type soft) |
| Expérience métier | 20% | Adéquation du parcours avec `job_experience` |
| Langues | 10% | Couverture des langues requises (`job_language`) |
| Adéquation générale (IA) | 20% | Analyse sémantique de la description par Gemini |

## Layer 1 — Pré-score (filtrage statique)
Calcul rapide basé uniquement sur les tables de la DB, **sans appel IA** :
- Nombre de `user_skills` correspondant à `job_skill` (pondéré par `is_required`)
- Présence des langues requises dans `user_language`
- Possession des permis requis dans `user_permit`
- Résultat : `pre_score` (0–100) stocké dans `user_matches`

**Seuil de passage** : Seules les offres avec un `pre_score ≥ 30` passent au Layer 2 (IA), pour limiter les coûts API.

## Layer 2 — Analyse Sémantique (IA)

### Structure du Prompt
```text
Tu es un expert en recrutement francophone belge.
Tu dois analyser la correspondance entre un candidat et une offre d'emploi.

## Profil du candidat
- Compétences : {user_skills_labels}
- Langues : {user_languages_with_levels}
- Profil libre : {user_profile_text}

## Offre d'emploi
- Titre : {job_title}
- Métier : {job_metier_label}
- Compétences requises : {job_skills_labels}
- Compétences souhaitées : {job_optional_skills_labels}
- Expérience demandée : {job_experience_label}
- Langues requises : {job_languages_with_levels}

## Description complète
{job_description_html_stripped}

## Consignes
- Évalue la compatibilité globale sur 100.
- Identifie les points forts et les points faibles du candidat par rapport à cette offre.
- Si des données sont manquantes (pas de compétences listées, description vague), indique-le dans les points faibles sans pénaliser excessivement le score.
- Réponds UNIQUEMENT en JSON valide, sans texte autour.

## Format de réponse
{
  "score": <int 0-100>,
  "points_forts": ["<string>", ...],
  "points_faibles": ["<string>", ...],
  "recommandation": "<string courte : Excellente correspondance | Bonne correspondance | Correspondance partielle | Faible correspondance>"
}
```

### Gestion des cas limites
| Cas | Comportement |
| :--- | :--- |
| Offre sans compétences listées | L'IA analyse uniquement la description textuelle, score plafonné à 70 |
| Description vide ou très courte | Score plafonné à 50, mentionné dans `points_faibles` |
| Candidat sans compétences renseignées | Layer 1 retourne `pre_score = 0`, Layer 2 non déclenché |
| Erreur API Gemini | Retry 2× avec backoff, puis marquer `analyzed_at = null` |
| Réponse JSON invalide | Retry 1×, puis stocker la réponse brute dans `ai_raw_response` et `ai_score = null` |

### Optimisations
- Utilisation des labels extraits des tables de référence pour donner du contexte riche à l'IA.
- Formatage JSON strict via `response_mime_type` pour une intégration directe en base.
- Stripping du HTML de `descriptionJob` avant envoi (réduction des tokens).
- Batch processing : traiter les offres par lots de 10 pour optimiser les quotas API.
