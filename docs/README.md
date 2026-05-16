# Documentation JobSearch (Forem Matcher AI)

Bienvenue dans la documentation officielle du projet **JobSearch**. Ce projet est une plateforme de matching d'offres d'emploi du Forem basée sur l'analyse de profils via Intelligence Artificielle (Gemini).

Pour faciliter la navigation et la compréhension du système, tant pour les développeurs humains que pour les agents IA (comme Jules), la documentation est structurée en quatre piliers principaux.

## 📂 Structure de la documentation

### 1. 🏗️ Architecture (`01-architecture/`)
Cette section contient les spécifications techniques fondamentales et le mapping des données.
- [`database.md`](01-architecture/database.md) : Schéma de la base de données, tables pivots et mapping des entités.
- [`ai-integration.md`](01-architecture/ai-integration.md) : Modèles, stratégies de prompt et barèmes utilisés avec Google Gemini.
- [`vectors.md`](01-architecture/vectors.md) : Explication du système d'embeddings et de la recherche vectorielle (Cosine Similarity).
- [`forem-api.md`](01-architecture/forem-api.md) : Référence des endpoints de l'API du Forem utilisés.
- [`workflow.md`](01-architecture/workflow.md) : Processus global de synchronisation et de traitement des offres.
- [`forem-import-strategies.md`](01-architecture/forem-import-strategies.md) : Logique des workers et requêtes API pour gérer les volumes d'offres.

### 2. 🧠 Logique Métier & Domaine (`02-domain-logic/`)
Cette section plonge dans le cœur de l'algorithme, expliquant *comment* et *pourquoi* le système fait ses choix.
- [`scoring-system.md`](02-domain-logic/scoring-system.md) : Philosophie fondamentale ("L'inversion du regard", pénalités, distance Haversine).
- [`matching-lifecycle.md`](02-domain-logic/matching-lifecycle.md) : L'entonnoir à 3 niveaux (Métier -> Structurel Layer 1 -> Sémantique Layer 2).
- [`dashboard-logic.md`](02-domain-logic/dashboard-logic.md) : Stratégies de récupération, eager loading, et filtrage pour l'UI.

### 3. 🎯 Produit & Vision (`03-product/`)
Cette section décrit la valeur ajoutée du produit et son avenir.
- [`vision.md`](03-product/vision.md) : L'objectif initial et la vision macro du projet.
- [`augmented-profile-vision.md`](03-product/augmented-profile-vision.md) : Le concept de profil narratif et les ponts d'expérience.
- [`roadmap.md`](03-product/roadmap.md) : L'état d'avancement des phases du projet.
- [`example-profile-synthesis.md`](03-product/example-profile-synthesis.md) : Exemple concret d'un profil analysé par l'IA.

### 4. 🛠️ Opérations & Déploiement (`04-ops/`)
Cette section est dédiée au lancement de l'application, tant en développement qu'en production.
- [`setup-and-tdd.md`](04-ops/setup-and-tdd.md) : **[IMPORTANT POUR LES AGENTS]** Le guide de bootstrap local, des commandes composer, et la philosophie TDD.
- [`deployment.md`](04-ops/deployment.md) : Guide pas-à-pas pour le déploiement sur serveur (ex: o2switch).
- [`deployment-worker.md`](04-ops/deployment-worker.md) : Stratégies de configuration des queues workers (Supervisor, Cron).
- [`production-readiness.md`](04-ops/production-readiness.md) : Suivi des correctifs et optimisations pour le passage en prod.

---

## 🤖 Note pour les Agents IA
Avant de démarrer toute intervention technique sur la base de code, vous **devez** :
1. Lire les règles impératives dans `AGENTS.md` à la racine.
2. Lire le guide [`04-ops/setup-and-tdd.md`](04-ops/setup-and-tdd.md) pour comprendre comment valider votre travail.
3. Vous référer au document spécifique du domaine touché (ex: `scoring-system.md` si vous modifiez la logique de match).