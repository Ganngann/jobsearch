# Workflow de Traitement

Le processus de matching suit un flux structuré pour garantir précision et performance.

## 1. Fetch & Sync
- Récupération de l'offre via l'API Forem.
- Si le code métier ou les codes compétences n'existent pas en base, ils sont créés dans les tables de référence (`metiers`, `skills`).
- L'offre est enregistrée et liée aux compétences via la table pivot `job_skill`.

## 2. Layer 1 - Filtrage Statique
- Calcul d'un "pré-score" basé sur le nombre de skills en commun entre `user_skills` et `job_skill`.
- Ce filtre permet de trier rapidement les opportunités les plus pertinentes avant l'analyse coûteuse par l'IA.

## 3. Layer 2 - Analyse Sémantique (IA)
- Gemini 1.5 Flash reçoit :
    - Le profil textuel de l'utilisateur.
    - La description complète du job.
    - Les compétences identifiées.
- L'IA affine le score en gérant les nuances et le contexte (ex: une compétence demandée mais non essentielle).

## 4. Persistance
- Mise à jour du score final dans `user_matches`.
- Enregistrement de l'analyse détaillée (points forts/faibles).
