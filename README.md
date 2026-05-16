# JobSearch (Forem Matcher AI)

**Forem Matcher AI** est une plateforme innovante d'optimisation de la recherche d'emploi. Contrairement aux plateformes classiques conçues pour les recruteurs, notre outil applique le principe de l'"inversion du regard" : il évalue l'attractivité d'une offre d'emploi (issue du Forem) pour le candidat, en fonction de ses contraintes de vie (distance, compétences, langues) et de son récit personnel analysé par l'IA (Google Gemini).

👉 **Pour comprendre en profondeur le projet, consultez notre [Documentation Complète](docs/README.md).**

## Stack Technique

- **Backend** : Laravel 13 (PHP 8.3)
- **Frontend** : Alpine.js, Tailwind CSS, Blade, Vite
- **Intelligence Artificielle** : API Google Gemini 2.5 Flash
- **Parsing** : smalot/pdfparser (Extraction de CV)
- **Base de données** : SQLite (Dév) / MySQL (Production)

## Installation & Démarrage Rapide

Le projet utilise des scripts personnalisés via Composer pour simplifier l'installation et le développement.

```bash
# 1. Initialise l'environnement complet (Dépendances, DB, clés, build Frontend)
composer setup

# 2. Lance les serveurs de développement (Laravel, Vite, Queues, Scheduler)
composer dev
```
*(Si vous utilisez **Laravel Herd**, lancez `composer dev-herd` à la place de `composer dev`)*

## Tests & Qualité (TDD)

Ce projet applique strictement la règle du **"No Test, No Commit"**.

```bash
# Lancer l'intégralité de la suite (Backend PHP + Frontend JS)
composer test

# Lancer uniquement les tests Backend (Laravel/PHPUnit)
php artisan test

# Lancer uniquement les tests Frontend (Vitest)
npm run test:js
```

## Architecture Orientée Services

Toute la logique métier est isolée dans `app/Services`, permettant des contrôleurs légers et une testabilité maximale :

- **`ForemApiService` & `JobOfferService`** : Importation massive et synchronisation des offres (via Pull Workers et Cron).
- **`MatchingService`** : Moteur principal de calcul de compatibilité (Modèle multiplicatif, Distance Haversine, Logique soustractive).
- **`AIProfileService` & `GeminiService`** : Analyse sémantique narrative et extraction de la dimension humaine.
- **`ResumeParserService` & `VectorService`** : Parsing de PDF et recherche par proximité sémantique (Cosine Similarity).

---

> **🤖 Note aux Agents IA (Jules, etc.)** : Avant de commencer à travailler, vous **devez** lire le fichier `AGENTS.md` à la racine et le guide `docs/04-ops/setup-and-tdd.md`.
