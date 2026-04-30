# Workflow de Traitement

Le processus de matching suit un flux structuré pour garantir précision et performance.

## 1. Fetch & Sync
- Récupération de l'offre via l'API Forem (Search + DetailOffre).
- Peuplement des tables de référence si les entrées sont nouvelles :
    - `metiers` (code ROME + GUID), `skills` (hard & soft)
    - `employers`, `sources`, `sectors`, `benefits`
    - `languages`, `permits`
- L'offre est enregistrée dans `jobs` avec ses clés étrangères.
- Les pivots sont alimentés : `job_skill`, `job_language`, `job_permit`, `job_benefit`, `job_sector`.

## 2. Layer 1 - Filtrage Statique
- Calcul d'un "pré-score" basé sur le nombre de skills en commun entre `user_skills` et `job_skill`.
- Ce filtre permet de trier rapidement les opportunités les plus pertinentes avant l'analyse coûteuse par l'IA.

## 3. Layer 2 - Analyse Sémantique (IA)
- Gemini 2.5 Flash reçoit :
    - Le profil textuel de l'utilisateur.
    - La description complète du job.
    - Les compétences identifiées.
- L'IA affine le score en gérant les nuances et le contexte (ex: une compétence demandée mais non essentielle).

## 4. Persistance
- Mise à jour du score final dans `user_matches`.
- Enregistrement de l'analyse détaillée (points forts/faibles).
