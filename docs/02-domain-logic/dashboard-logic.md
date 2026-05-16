# Documentation Technique : Affichage des Offres sur le Dashboard

Ce document explique le fonctionnement technique de la récupération, du filtrage et de l'affichage des offres d'emploi sur le tableau de bord utilisateur de **Forem Matcher AI**.

## 1. Architecture Globale

L'affichage est géré principalement par le `JobOfferController` via la méthode `dashboard()`. Le système utilise **Laravel Blade** pour le rendu et **Tailwind CSS** pour le design.

### Flux de données optimisé :
1. L'utilisateur accède à la route `/dashboard`.
2. Le système interroge la table `user_matches` (déjà filtrée sur `user_id` et `score > 30%`).
3. On récupère les détails des `job_offers` par jointure simple sur les IDs matchés.
4. Les données sont paginées et envoyées à la vue.
5. **État de secours** : Si le profil est incomplet, le flux est suspendu et l'utilisateur est redirigé vers l'onboarding.

---

## 2. Logique de Récupération (Controller)

Le `JobOfferController` applique plusieurs couches de logique avant d'envoyer les données à la vue :

### Eager Loading
Pour optimiser les performances (éviter le problème N+1), nous chargeons les relations immédiatement :
- `employer` : Informations sur l'entreprise.
- `metier` : Classification métier.
- `matches` : Relation filtrée sur l'ID de l'utilisateur actuel pour obtenir son score.

### Filtrage Dynamique
Le dashboard supporte trois filtres principaux :
- **Recherche textuelle** : Recherche dans le titre de l'offre ou le nom de l'employeur.
- **Type de contrat** : Filtre exact sur le champ `contract_type`.
- **Score Minimum** : Filtre les offres via une sous-requête sur la table `user_matches`. Ce filtre s'appuie sur le **Pré-score (Layer 1)** pour éliminer les bruits.

### Système de Tri
L'utilisateur peut trier les offres selon :
- **Meilleur match** (Défaut) : Trie par `COALESCE(final_score, pre_score)` décroissant.
- **Plus récent** : Basé sur `published_at`.
- **Titre (A-Z)** : Tri alphabétique.

---

## 3. Système de Score et Matching (Stratégie Multicouche)

Pour gérer le volume de 40 000+ offres sans saturer l'IA, le système utilise deux couches de scoring :

### Layer 1 : Le Pré-score Automatique (Conception)
Calculé instantanément sans IA à partir des données structurées. Il sert de premier tri pour ne présenter que le "haut du panier" à l'analyse sémantique.
- **Cœur du Match** : ID Métier (ROME), Compétences techniques.
- **Filtres bloquants** : Permis, Langues, Localisation.
- **Objectif** : Faire remonter les offres structurellement compatibles à 100%.

### Layer 2 : L'Analyse IA Sémantique (Final Score)
L'IA (Gemini) intervient pour analyser la "Dimension Humaine" (récits, aspirations) et la description textuelle complète. Elle n'est déclenchée que sur demande ou si le Layer 1 est prometteur.

### Visualisation (UI)
Le score est coloré dynamiquement pour une lecture rapide :
- <span style="color: #10b981;">**Vert (>= 70%)**</span> : Excellente correspondance.
- <span style="color: #f59e0b;">**Orange (40-69%)**</span> : Correspondance partielle.
- <span style="color: #94a3b8;">**Gris (< 40%)**</span> : Faible correspondance.

Une barre de progression subtile en bas de chaque carte d'offre renforce cette hiérarchie visuelle.

---

## 4. Synchronisation Lazy-Loading

Le système utilise une stratégie de synchronisation "au besoin" pour économiser les ressources API :

1. **Scan initial** : Seules les données de base (titre, employeur, réf) sont importées massivement.
2. **Détail Complet** : Lorsqu'un utilisateur clique sur une offre ou demande un matching IA, le système vérifie le flag `is_detailed`.
3. **Sync API** : Si `is_detailed` est à `false`, le `JobOfferService` appelle l'API Forem pour récupérer la description complète, les compétences requises, les langues, etc.

---

## 5. Actions Utilisateur

Depuis le dashboard, l'utilisateur peut :
- **Consulter** : Accéder à la fiche détaillée (`jobs.show`).
- **Calculer Match** : Déclencher manuellement l'analyse IA si elle n'a pas été faite automatiquement.
- **Filtrer/Trier** : Affiner sa recherche via la barre de filtres supérieure.
- **Rechercher sur le Forem** : Redirection vers l'outil de recherche externe pour importer de nouvelles offres dans sa base personnelle.

---

## 6. Fichiers Clés

- **Contrôleur** : `app/Http/Controllers/JobOfferController.php`
- **Vue** : `resources/views/dashboard.blade.php`
- **Service de Matching** : `app/Services/MatchingService.php`
- **Modèle Offre** : `app/Models/JobOffer.php`

---

## 7. Gestion des Compétences Négatives (Spécifications de Réflexion)

Le système distingue deux types d'exclusions pour affiner le profil et le matching :

### A. Les Compétences "Inhibées" (Exactitude du Profil)
- **Concept** : "Je ne possède pas cette compétence".
- **Rôle** : Empêche l'IA de déduire cette compétence lors de la génération automatique du profil (évite les hallucinations basées sur le contexte).
- **Impact Matching** : Neutre. L'offre demandant cette compétence n'est pas pénalisée, elle est simplement vue comme une opportunité de montée en compétence.

### B. Les Compétences "Proscrites" (Veto de l'Aspiration)
- **Concept** : "Je ne veux pas travailler dans un poste exigeant cela".
- **Rôle** : Agit comme un répulsif de matching.
- **Impact Matching** : **Malus Critique**. Toute offre exigeant une compétence proscrite subit une pénalité majeure (ex: Score -100) pour être exclue des résultats pertinents.
