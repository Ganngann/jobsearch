# JobSearch

Plateforme de matching d'offres d'emploi basée sur l'analyse de profils via IA.

## Stack Technique

- **Backend** : Laravel 13 (PHP 8.3)
- **Frontend** : Alpine.js, Tailwind CSS, Vite
- **Services** : Google Gemini (LLM), smalot/pdfparser (Parsing PDF)
- **Base de données** : SQLite

## Installation

```bash
composer setup
```

## Développement

Lancement de l'environnement complet (Serveur, Queue, Pail, Vite) :
```bash
composer dev
```

## Tests

```bash
composer test
```

## Structure Technique

Le projet repose sur une architecture orientée services (`app/Services`) pour isoler la logique métier :

- **ResumeParserService** : Extraction de données structurées depuis les fichiers PDF.
- **AIProfileService** & **ProfileMappingService** : Enrichissement et structuration des profils utilisateurs via LLM.
- **ForemApiService** : Ingestion de données depuis des API tierces.
- **JobMatcherService** & **MatchingService** : Moteur de calcul de compatibilité entre profils et offres.
- **DiscoveryService** : Logique de recommandation d'offres.
