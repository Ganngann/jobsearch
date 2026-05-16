# Guide de Déploiement en Production - Forem Matcher AI

Ce guide détaille les étapes nécessaires pour mettre en production le système de synchronisation massive et le moteur de matching IA.

## 1. Pré-requis Serveur
*   **PHP 8.2+** avec extensions : `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_sqlite` (ou MySQL/PostgreSQL).
*   **Composer** (Gestion des dépendances).
*   **Gestionnaire de processus** : Supervisor (recommandé pour le Pull Worker).
*   **Accès Internet** : Le serveur doit pouvoir contacter `https://www.leforem.be`.

## 2. Configuration de l'Environnement (`.env`)
Assurez-vous que les variables suivantes sont correctement configurées :
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Configuration IA
GEMINI_API_KEY=votre_cle_api

# Cache & Files d'attente (Redis recommandé pour la production)
CACHE_STORE=file
QUEUE_CONNECTION=database
```

## 3. Mise en Place du Scheduler (Cron)
Le système repose sur des tâches planifiées. Ajoutez cette ligne à la crontab de l'utilisateur système :
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Tâches planifiées automatiques :**
*   `forem:scan --mode=flash` : Toutes les 5 minutes.
*   `forem:scan --mode=cycle` : Toutes les 15 minutes.
*   `forem:pull-worker` : Toutes les minutes (limité à 10 itérations par défaut).

## 4. Gestion du Pull Worker (Performance Maximale)
Le "Pull Worker" est responsable de la récupération des détails complets. Si vous avez un volume énorme (> 50k offres) et que vous voulez une synchronisation plus rapide que celle du scheduler, utilisez **Supervisor**.

### Configuration Supervisor Suggérée
Créez `/etc/supervisor/conf.d/forem-worker.conf` :
```ini
[program:forem-pull-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-project/artisan forem:pull-worker --sleep=5 --limit=500
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-your-project/storage/logs/forem-worker.log
```
*Note : Réglez `--sleep` entre 3 et 10 pour rester poli avec l'API du Forem.*

## 5. Maintenance et Nettoyage
Le système gère l'archivage automatique via `forem:scan`. Les offres non vues depuis plus de 24h passent en `status = 'archived'`.

### Indexation SQL
Vérifiez que l'index de performance est bien présent (ajouté via migration) :
```sql
CREATE INDEX idx_sync_priority ON job_offers (status, is_detailed, last_seen_at, detailed_at);
```

## 6. Monitoring
*   **Logs** : Surveillez `storage/logs/laravel.log` pour les erreurs d'API.
*   **Santé du Scan** : Vérifiez la valeur de `forem_scan_cycle_page` dans le cache pour voir la progression du cycle complet.
    ```bash
    php artisan cache:get forem_scan_cycle_page
    ```
