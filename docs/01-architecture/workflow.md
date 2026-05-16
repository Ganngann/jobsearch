# Workflow de Traitement

Le processus de matching suit un flux structuré pour garantir précision et performance.

## 1. Fetch & Sync (Commande Artisan)

### Étape 1a : Discovery (Search)
- Appel à l'endpoint Search avec pagination (`page`, `row`).
- Pour chaque offre résumée :
    - Créer ou mettre à jour `employers` (avec `logo_uuid`).
    - Créer ou mettre à jour `sectors` → alimenter le pivot `job_sector`.
    - Stocker les dates ISO (`debut` → `published_at`, `fin` → `expires_at`).
    - Capturer `telephone` (uniquement disponible ici) → `contact_phone`.
    - Capturer `nombrePostes` → `nombre_postes`.
    - Enregistrer l'offre dans `jobs` avec les données résumées.

### Étape 1b : Detail (DetailOffre)
- Pour chaque offre nouvellement importée ou à rafraîchir :
    - Appel à `DetailOffre/{forem_id}`.
    - Enrichir `employers` : `logo_base64`, `logo_mime_type`, `description`.
    - Alimenter les tables de référence si entrées nouvelles :
        - `metiers` (via `experience[].code` pour le ROME, `metier` pour le label)
        - `skills` (depuis `competencies[]` type hard + `softSkills[]` type soft)
        - `languages` (depuis `langues[]` avec code et libellé)
        - `permits` (depuis `permisConduire[]`)
        - `studies` (depuis `etudes[]`)
        - `benefits` (depuis `benefits.otherBenefits[]`)
    - Alimenter les pivots :
        - `job_skill` (avec `is_required`)
        - `job_language` (avec `level`)
        - `job_permit` (avec `is_required`)
        - `job_study`
        - `job_benefit`
        - `job_experience` (avec `experience_label` et `is_required`)
    - Enrichir `jobs` : `description`, `working_hours`, `shift_period`, `base_salary`, `benefits_comments`, `start_date`, `contact_name`, `apply_instructions`.
    - Stocker la réponse brute dans `raw_data`.

### Stratégie de parsing des dates
- `published_at` et `expires_at` : depuis Search (ISO 8601, fiable).
- `start_date` : depuis Detail (`positionDateInfo.startDate`, format `DD/MM/YYYY`).
- **Ne pas utiliser** `datePublication` du Detail (label humain non parsable).

## 2. Layer 1 — Score Sémantique (Fond)
- Comparaison locale (Cosine Similarity) entre le vecteur de l'utilisateur et le vecteur de l'offre.
- Résultat : `vector_score` (0-100) représentant la pertinence métier brute.

## 3. Layer 2 — Pré-score d'Attractivité (Forme)
- Pour chaque utilisateur, on calcule une attractivité en partant de 100 et en appliquant des pénalités (friction) :
    - Distance (localisation / télétravail).
    - Compétences, permis, langues requis manquants (ou refusés).
    - Contrat ne correspondant pas aux préférences.
    - Vétusté de l'offre.
    - Des bonus peuvent s'appliquer (ex: métier favori).
- Résultat : `pre_score` (0–100).

## 4. Layer 3 — Expertise IA (Optionnel/À la demande)
- Une analyse narrative avec Gemini 2.5 Flash est déclenchée (manuellement ou si conditions réunies) :
    - L'IA évalue la dimension humaine (Récits, aspirations, soft skills invisibles).
    - L'IA retourne un JSON structuré : `score`, `points_forts`, `points_faibles`, `analyse_narrative`, `recommandation`.
- Ce score IA, s'il est calculé, remplace le score produit comme expertise finale.

## 5. Persistance
- Mise à jour dans `user_matches` :
    - `vector_score` : score sémantique.
    - `pre_score` : attractivité calculée.
    - `final_score` : `ai_score` (si existant) OU `vector_score * (pre_score / 100)`.
    - `strengths` / `weaknesses` : points forts/faibles (JSON).
    - `ai_analysis_narrative` : analyse textuelle ultra-concise de Gemini.
    - `ai_raw_response` : réponse brute de Gemini.
    - `analyzed_at` : timestamp de l'analyse.
