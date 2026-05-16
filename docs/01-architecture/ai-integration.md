# Stratégie de Scoring & IA

## Modèle : Google Gemini 2.5 Flash

### Configuration
- **Temperature** : `0.2` (réponses déterministes et factuelles)
- **Max Output Tokens** : `1024`
- **Response MIME Type** : `application/json`
- **Langue de réponse** : Français obligatoire

## Architecture de Scoring

Le scoring repose sur un système à 3 couches (Layers) alliant matching vectoriel local, calcul métier d'attractivité, et analyse narrative IA à la demande.

## Layer 1 — Score Sémantique (Fond)
- Comparaison sémantique **entièrement locale** et gratuite (Cosine Similarity) entre les embeddings vectoriels pré-générés du candidat et de l'offre.
- Renvoie un `vector_score` (0-100) représentant la correspondance brute métier/compétences.
- Utilisé comme base si aucune analyse IA spécifique (Layer 3) n'a été demandée.

## Layer 2 — Pré-score d'Attractivité (Forme)
- Calcul basé sur la méthode de **l'Inversion du Regard**. On part d'un score de 100 et on retire des points pour les éléments de friction.
- Exemples de friction :
    - Distance ou temps de trajet excessif.
    - Métier ou compétences refusés.
    - Contrat non souhaité.
    - Langues ou permis requis manquants.
    - Ancienneté de l'offre (vétusté).
- Produit le `pre_score` (0-100).
- Le score final combiné par défaut est le produit : `vector_score * (pre_score / 100)`.

## Layer 3 — Expertise IA (Analyse Narrative)

L'IA est invoquée uniquement sur demande (manuelle ou via un top 20). Si l'IA donne son expertise, son score (`ai_score`) devient le score maître et remplace le produit de `Layer 1 * Layer 2`.

### Structure du Prompt
```text
Tu es un expert en recrutement francophone belge, spécialisé dans l'approche narrative et humaine (Dimension Humaine).
Ta mission est d'analyser la correspondance entre un candidat et une offre d'emploi en allant au-delà des simples mots-clés techniques.

## 1. PROFIL DU CANDIDAT
- Titre/Headline : {user_headline}
- Bio/Résumé : {user_profile_text}
- Aspirations : {user_aspirations}
- Compétences Techniques : {user_hard_skills}
- Soft Skills : {user_soft_skills}
- Langues : {user_languages}
- Mobilité : Rayon maximum de {user_radius} km autour de son domicile.
- Préférences de contrat : {user_contract_preferences}

## 2. RÉCITS & EXPÉRIENCES CONCRÈTES (La preuve par le fait)
Voici les éléments narratifs validés par le candidat qui prouvent ses compétences et sa résilience :
{user_facts}

## 3. L'OFFRE D'EMPLOI
- Titre : {job_title}
- Métier : {job_metier_label}
- Type de contrat : {job_contract_type}
- Compétences requises : {job_skills}
- Compétences souhaitées : {job_optional_skills}
- Langues requises : {job_languages}
- Localisation de l'offre : {job_location}

## 4. DESCRIPTION COMPLÈTE DE L'OFFRE
{job_description_html_stripped}

## TA MISSION
1. Analyse comment les récits concrets du candidat répondent aux besoins du poste.
2. Identifie les "soft skills" invisibles mais présents dans les récits (résilience, adaptabilité, etc.).
3. Évalue si les aspirations du candidat sont en phase avec le poste.
4. Analyse la faisabilité géographique : si la distance réelle dépasse le rayon souhaité, mentionne-le comme un point d'attention ou de vigilance, mais pondère-le en fonction de la distance.
5. Vérifie si le type de contrat de l'offre correspond aux préférences du candidat. Si ce n'est pas le cas, cela doit impacter négativement le score et être mentionné comme point faible.
6. Calcule un score global (0-100).

CONSIGNE DE STYLE : Sois EXTRÊMEMENT CONCIS. L'analyse narrative doit faire 3 lignes maximum, en allant droit au but.

Réponds UNIQUEMENT en JSON avec cette structure :
{
    "score": (int),
    "points_forts": [string],
    "points_faibles": [string],
    "analyse_narrative": "(Analyse ultra-concise, max 3 phrases)",
    "recommandation": "(Un seul conseil court)"
}
```

### Gestion des cas limites
| Cas | Comportement |
| :--- | :--- |
| Offre avec données pauvres | Mentionné comme boolean (`is_poor_data`), impact direct limité mais score sémantique potentiellement plus bas |
| Erreur API Gemini | Retry avec backoff, puis marquer `analyzed_at = null` |
| Réponse JSON invalide | Retourne null, relancé plus tard si besoin |

### Optimisations
- Utilisation des labels extraits des tables de référence pour donner du contexte riche à l'IA.
- Formatage JSON strict via `response_mime_type` pour une intégration directe en base.
- Stripping du HTML de `descriptionJob` avant envoi (réduction des tokens).
- Batch processing : traiter les offres par lots de 10 pour optimiser les quotas API.
