# Guide de Configuration Locale et TDD pour Agents Jules

Ce document s'adresse aux agents IA (comme Jules) intervenant sur le projet Laravel **JobSearch**. Il explique comment initialiser l'environnement de développement, lancer l'application et appliquer une approche de développement dirigé par les tests (TDD).

## 1. Initialisation de l'Environnement (Setup)

Avant de commencer à travailler, assure-toi que le projet est prêt en utilisant le script personnalisé défini dans `composer.json`.

```bash
composer setup
```

**Ce que fait cette commande :**
- Installe les dépendances PHP (`composer install`).
- Crée le fichier `.env` s'il n'existe pas (copie de `.env.example`).
- Génère la clé d'application (`php artisan key:generate`).
- Exécute les migrations de la base de données SQLite (`php artisan migrate --force`).
- Installe les dépendances Node.js (`npm install`).
- Compile les assets frontend (`npm run build`).

Si tu rencontres des problèmes de timeout lors du setup, exécute `composer dump-autoload --no-scripts` en guise de solution de contournement temporaire.

## 2. Démarrage de l'Environnement Local

Le projet utilise des files d'attente (queues), des tâches planifiées (cron) et un bundle Vite. Un script dédié lance tout en parallèle.

```bash
composer dev
```

**Cette commande démarre concurremment :**
- Le serveur local de développement Laravel.
- Le worker pour écouter les queues (`queue:listen`).
- Le worker pour les tâches planifiées (`schedule:work`).
- Le serveur de développement Vite pour le frontend.

*(Si l'environnement utilise Laravel Herd, utilise plutôt `composer dev-herd`)*.

## 3. Workflow TDD (Test-Driven Development)

Il est **strictement requis** ("No Test, No Commit") de s'assurer que chaque modification ou nouvelle fonctionnalité est couverte par un test avant la soumission, et que l'intégralité de la suite de tests réussisse.

Le projet inclut des tests backend (PHPUnit via Laravel) et frontend (Vitest pour Alpine.js/JS).

### Exécuter l'intégralité de la suite de tests (Backend + Frontend)

Avant de créer une pull request ou de soumettre le code, exécute la commande complète :

```bash
composer test
```
*(Cela exécute `php artisan test` suivi de `npm run test:js`).*

### Exécuter uniquement les tests Backend (Laravel)

Pendant le développement d'une action métier ou d'un service (ex: `app/Services/MatchingService.php`) :

```bash
php artisan test
```

Pour cibler un test spécifique, utilise le flag `--filter` :
```bash
php artisan test --filter NomDuTest
```

### Exécuter uniquement les tests Frontend (Vitest)

Pendant le développement ou la modification de composants Alpine.js ou de la logique JavaScript :

```bash
npm run test:js
```

## Règles Critiques pour les Agents

1. **Source de vérité :** Utilise toujours ce qui est dans les fichiers de code plutôt que les mémoires contextuelles si elles diffèrent.
2. **Ne modifie pas les fichiers de lock :** Ne touche pas à `composer.lock` ni à `package-lock.json` sans instruction explicite.
3. **Vérification systématique :** Après toute commande modifiant des fichiers (édition de code, migration), utilise des commandes de lecture (ex: `read_file`, `cat`, `ls`) pour confirmer le succès de l'action avant de passer à la suite.
4. **Architecture Services :** Toute logique métier complexe doit être implémentée dans les Services Laravel, pas dans les Contrôleurs ou Modèles, pour faciliter les tests isolés.
