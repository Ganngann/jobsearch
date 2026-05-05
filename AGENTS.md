# Documentation Technique (Agents)

## Services Principaux

| Service | Rôle |
| :--- | :--- |
| `GeminiService` | Interface avec l'API Google Gemini. |
| `ResumeParserService` | Extraction d'entités depuis les CV. |
| `AIProfileService` | Génération de données de profil structurées. |
| `MatchingService` | Algorithmes de calcul de score. |
| `JobMatcherService` | Orchestration du matching. |
| `ForemApiService` | Synchronisation avec les APIs externes. |
| `JobOfferService` | Gestion CRUD et cycle de vie des offres. |

## Conventions de Développement

1. **Architecture** : Toute la logique métier doit résider dans les Services. Les Contrôleurs et Modèles doivent rester légers.
2. **IA** : L'enrichissement de données doit passer par `GeminiService`.
3. **Frontend** : Utilisation exclusive d'Alpine.js pour l'interactivité et Tailwind CSS pour le style.
4. **Asynchronisme** : Les tâches lourdes (matching massif) sont déléguées aux queues Laravel.

## Commandes Utiles

- `composer setup` : Initialisation complète de l'environnement.
- `composer dev` : Lancement du serveur, de la queue et de Vite via `concurrently`.
- `composer test` : Exécution de la suite de tests complète (Backend Laravel + Frontend Vitest).
- `npm run test:js` : Exécution des tests unitaires Javascript uniquement.
- `php artisan test` : Exécution des tests Laravel uniquement.

## Règles Critiques

- Ne jamais exécuter de commandes `git` sauf demande explicite.
- Toujours vérifier la stabilité avec `composer test` après modification de la logique de matching ou de parsing.
- Respecter les patterns Laravel 13 (Injection de dépendances, Type hinting).
