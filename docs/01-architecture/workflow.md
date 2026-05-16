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

## 2. Layer 1 — Filtrage Statique
- Pour chaque utilisateur, calculer un `pre_score` (0–100) :
    - Compter les `user_skills` ∩ `job_skill` (pondéré par `is_required`).
    - Vérifier la couverture des `job_language` requises.
    - Vérifier la possession des `job_permit` requis.
- Stocker le `pre_score` dans `user_matches`.
- **Seuil** : Seules les offres avec `pre_score ≥ 30` passent au Layer 2.

## 3. Layer 2 — Analyse Sémantique (IA)
- Gemini 2.5 Flash reçoit :
    - Le profil textuel de l'utilisateur (`profile_text` + labels des compétences).
    - La description complète du job (HTML strippé).
    - Les compétences identifiées et leur statut obligatoire/optionnel.
    - Les métiers d'expérience requis.
- L'IA retourne un JSON structuré : `score`, `points_forts`, `points_faibles`, `recommandation`.
- Gestion d'erreurs : retry avec backoff, fallback sur `ai_score = null`.

## 4. Persistance
- Mise à jour dans `user_matches` :
    - `ai_score` : score IA (0–100).
    - `final_score` : combinaison pondérée de `pre_score` et `ai_score`.
    - `strengths` / `weaknesses` : points forts/faibles (JSON).
    - `ai_raw_response` : réponse brute de Gemini.
    - `analyzed_at` : timestamp de l'analyse.
