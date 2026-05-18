# Guide de Mise en Ligne (Production)

Ce document détaille les étapes nécessaires pour déployer l'application **JobSearch** sur un serveur de production avec une infrastructure robuste.

## 1. Prérequis Système

- **PHP 8.3+** avec les extensions suivantes :
    - `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `session`, `tokenizer`, `xml`, `mysql` (ou `pgsql`)
- **Base de données** : MySQL 8.0+ ou MariaDB 10.6+ (PostgreSQL également supporté)
- **Redis** : Recommandé pour le Cache, les Sessions et les Queues
- **Composer** (dernière version stable)
- **Node.js & NPM** (pour le build des assets)
- **Supervisor** : Indispensable pour gérer les processus en arrière-plan

---

## 2. Déploiement Initial

### Cloner et Préparer les Fichiers
```bash
# Récupérer le code
git clone <repository-url> /var/www/jobsearch
cd /var/www/jobsearch

# Installer les dépendances PHP
composer install --no-dev --optimize-autoloader
```

### Configuration de l'Environnement
```bash
cp .env.example .env
php artisan key:generate
```
Modifiez le fichier `.env` pour configurer les paramètres de production :

**Application & Sécurité** :
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://votre-domaine.com`

**Base de Données (MySQL)** :
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=jobsearch`
- `DB_USERNAME=votre_user`
- `DB_PASSWORD=votre_password`

**Drivers Recommandés (Redis)** :
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

**AI Service** :
- `GEMINI_API_KEY=votre_cle_api`

### Base de Données & Seeding
```bash
# Exécution des migrations
php artisan migrate --force

# Initialisation des données de référence obligatoires
php artisan db:seed --class=LanguageSeeder
php artisan db:seed --class=PermitSeeder
php artisan db:seed --class=ReferentielMetierSeeder
php artisan db:seed --class=ZipCodeSeeder
```
> [!IMPORTANT]
> Assurez-vous que le fichier `belgian_zipcodes.csv` est présent à la racine pour le `ZipCodeSeeder`.

### Build des Assets
```bash
npm install
npm run build
```

---

## 3. Permissions et Sécurité

```bash
chown -R www-data:www-data /var/www/jobsearch
chmod -R 775 /var/www/jobsearch/storage
chmod -R 775 /var/www/jobsearch/bootstrap/cache
```
*Note : L'application force le HTTPS automatiquement en production via `AppServiceProvider`.*

---

## 4. Optimisations Performance

Exécutez ces commandes à chaque déploiement :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Automatisation (Supervisor & Cron)

### Planificateur (Cron)
Ajoutez au crontab (`crontab -e`) :
```bash
* * * * * cd /var/www/jobsearch && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Workers (Supervisor)
Configurez un worker pour traiter les analyses IA et le matching en arrière-plan.
Fichier : `/etc/supervisor/conf.d/jobsearch-worker.conf`
```ini
[program:jobsearch-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/jobsearch/artisan queue:work redis --queue=high,default,low --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/jobsearch/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 7. Spécificités o2switch (Hébergement Mutualisé)

o2switch est un hébergement performant, mais étant mutualisé, il impose quelques ajustements par rapport à un serveur dédié.

### Configuration du dossier Public
Par défaut, o2switch utilise `public_html`. Pour Laravel :
1. Déposez votre projet dans un dossier à la racine (ex: `~/jobsearch`).
2. Dans le cPanel, allez dans **Domaines** et modifiez le "Document Root" pour qu'il pointe vers `/jobsearch/public`.

### Version PHP
Assurez-vous de sélectionner **PHP 8.3** dans l'outil **Sélectionner une version de PHP** du cPanel. Activez les extensions `pdo_mysql`, `intl`, `bcmath`.
> [!TIP]
> Dans les options PHP du cPanel, augmentez le `memory_limit` à **512M** ou plus pour supporter les calculs de matching et la vectorisation.

### Files d'attente (Queues) sans Supervisor
o2switch ne permet pas d'installer Supervisor. Pour traiter les jobs :
1. **Option Cron** (Recommandée) : Ajoutez cette tâche cron toutes les minutes :
   ```bash
   * * * * * /usr/local/bin/php /home/votreuser/jobsearch/artisan queue:work --queue=high,default,low --stop-when-empty >> /dev/null 2>&1
   ```
2. **Terminal SSH** : Vous pouvez lancer un worker manuellement dans le terminal SSH, mais il risque d'être arrêté si la session se ferme.

### Optimisation Tiger
Désactivez ou configurez avec précaution l'outil "Tiger" (Varnish) d'o2switch sur les routes dynamiques de l'application pour éviter les problèmes avec les jetons CSRF et les sessions.

---

## 8. Checklist de Mise en Prod

1. [ ] **HTTPS** : Activer "Let's Encrypt" dans cPanel.
2. [ ] **Stockage** : Exécuter `php artisan storage:link`.
3. [ ] **Logs** : Vérifier que `storage/logs/laravel.log` est accessible en écriture.
4. [ ] **Database** : Créer la base MySQL via le cPanel et noter les accès.
5. [ ] **Node.js** : Build les assets en local ou via le terminal SSH d'o2switch.
