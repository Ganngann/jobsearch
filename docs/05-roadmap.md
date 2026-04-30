# Roadmap & Évolutions

## [x] Phase 0 : Conception
- Identification des 3 API Forem (Search, Detail, Facettes).
- Architecture normalisée V4 :
    - 9 tables de référence (metiers, skills, sectors, employers, sources, benefits, languages, permits, studies).
    - 7 tables pivot (job_skill, job_language, job_permit, job_benefit, job_sector, job_study, job_experience).
    - Table users + 3 tables user_* (user_skill, user_language, user_permit).
    - Table user_matches avec scoring Layer 1 + Layer 2.
- Stratégie de scoring IA documentée (barème 0–100, prompt structuré).
- Mapping API → DB complet avec gestion des pièges (dates, logos, téléphone).
- Pile technique définie (Laravel 11, SQLite/MySQL, Gemini 2.5 Flash).

## [x] Phase 1 : MVP (Backend Focus)
- [x] Initialisation du projet Laravel 11.
- [x] Migrations pour toutes les tables (ref, job_offers, pivots, users, matches).
- [x] Models Eloquent avec relations.
- [x] Commandes Artisan :
    - [x] `forem:sync` : Fetch Search + Detail, alimentation DB.
    - [x] `forem:match` : Layer 1 (pré-score) + Layer 2 (IA).
- [x] Intégration de l'API Gemini 2.0 Flash (Service prêt).
- [ ] Tests unitaires sur le parsing API et le scoring.

## [ ] Phase 2 : Interface Utilisateur
- Dashboard Laravel/Blade + Tailwind CSS.
- Gestion du profil utilisateur (compétences, langues, permis).
- Recherche et filtrage des offres par compétences, secteur, lieu.
- Visualisation des scores et analyses détaillées.
- Export des résultats (PDF/CSV).

## [ ] Phase 3 : SaaS & Scalabilité
- Gestion multi-profils (plusieurs utilisateurs).
- Système d'alertes intelligentes (Email/Push).
- Statistiques sur le marché de l'emploi local.
- Historique et tendances des scores.
