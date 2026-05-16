# Cycle de Vie et Stratégie de Matching

Ce document définit le flux de travail (workflow) pour le calcul des scores de compatibilité, en articulant l'importation massive des données Forem avec l'analyse intelligente (IA).

## 1. Philosophie du Matching "Juste à Temps"

Avec plus de 40 000 offres actives, le système doit minimiser les calculs inutiles. La stratégie repose sur un entonnoir de décision à trois étapes.

### Étape 1 : Le Filtrage Structurel (Lors du Scan)
Dès qu'une offre est détectée par le **Scan de Synchronisation** :
- **Moment** : Lors de l'upsert initial.
- **Données** : Titre, ID Métier (ROME), Localisation.
- **Action** : Calcul d'un "Score de Surface".
- **Objectif** : Identifier immédiatement si l'offre appartient à la sphère d'intérêt de l'utilisateur. Si l'ID Métier ne match pas, l'offre est de facto reléguée sans plus de traitement.

### Étape 2 : Le Pré-score Robuste (Layer 1 - Lors du Détail)
Une fois que le **Pull Worker** a récupéré le détail complet de l'offre :
- **Moment** : Immédiatement après la mise à jour des champs (compétences, langues, permis).
- **Calcul** : Somme pondérée des compétences (Must-have vs Nice-to-have) + Langues + Permis.
- **Filtres Veto** :
    - Application des malus pour les **Compétences Proscrites** (Veto de l'utilisateur).
    - Malus critiques si un pré-requis (langue/permis) est absent du profil.
- **Résultat** : Un score de 0 à 100 qui définit la pertinence structurelle réelle.

### Étape 3 : L'Analyse Sémantique IA (Layer 2 - Analyse Humaine)
L'analyse par IA n'est déclenchée que pour la "short-list" finale.
- **Conditions de déclenchement** :
    1. Le Pré-score (Layer 1) est supérieur à un seuil défini (ex: > 70%).
    2. OU l'utilisateur demande explicitement une analyse sur une offre.
- **Moment** : En tâche de fond (Queue) ou à la demande.
- **Objectif** : Analyser la "Dimension Humaine" (récits du candidat vs description narrative de l'offre).

---

## 2. Synthèse du Flux

| État de l'Offre | Action | Type de Score | Priorité |
| :--- | :--- | :--- | :--- |
| **Nouveau Scan** | `Initial Match` | Score Métier / Titre | Immédiat (Sync) |
| **Après Détails** | `Layer 1 calculation` | Score Structurel Complet | Background (Worker) |
| **Si Score L1 > X** | `Layer 2 calculation` | IA / Analyse Narrative | Background (Queue) |

---

## 3. Gestion des Changements (Invalidation)

Le score de matching doit rester cohérent avec l'évolution des données.

### Invalidation par l'Offre
Si le `content_hash` de l'offre change (mise à jour par le Forem) :
- Le score IA est supprimé.
- Le Pré-score est recalculé.
- Si le nouveau Pré-score est toujours élevé, une nouvelle analyse IA est planifiée.

### Invalidation par le Profil Utilisateur
Si l'utilisateur modifie ses compétences ou ses préférences (Métiers préférés / Compétences proscrites) :
- **Action** : Réinitialisation des scores impactés.
- **Changement de Métier** : 
    1. Suppression des anciens matchs devenus hors-sujet.
    2. Scan de la table `job_offers` pour identifier toutes les offres correspondant aux nouveaux métiers.
    3. Planification en masse (Batch) du calcul du Layer 1 pour ces nouvelles opportunités.
- **Optimisation** : Le système replanifie en priorité le calcul pour les offres les plus récentes.

---

## 4. Paramètres de Performance

- **Seuil de passage à l'IA** : Configurable (Défaut : 70%).
- **Fréquence du Pull Worker** : 1 requête toutes les 3-10 secondes.
- **Mise en cache** : Les résultats du Layer 1 sont indexés en base de données pour permettre un tri/filtrage instantané sur le Dashboard.

---

## 5. Passage à l'Échelle (Multi-utilisateurs)

Le défi de 10k utilisateurs face à 40k offres (400 millions de combinaisons potentielles) est géré par deux leviers :

### A. Filtrage par Segmentation (Broadcast sélectif)
Le calcul du Layer 1 n'est jamais fait en "aveugle" sur toute la base.
1. Chaque nouvelle offre est catégorisée par son **Code Métier (ROME)**.
2. Le système identifie la cohorte d'utilisateurs ayant ce métier dans leurs préférences.
3. Le matching n'est calculé **que pour cette cohorte**.

---

## 6. Analyse de l'Impact Serveur et Optimisations

Le système est conçu pour être "économe" en ressources tout en gérant un volume massif.

| Composant | Impact | Nature de la charge | Stratégie d'atténuation |
| :--- | :--- | :--- | :--- |
| **Calcul Layer 1** | **Moyen** | CPU (Maths) & SQL (Jointures) | **Persistance Systématique** : Le score est calculé une fois et stocké. Jamais calculé lors de l'affichage. |
| **Analyse Layer 2** | **Faible** | Réseau (API Externe) | Utilisation de workers asynchrones (Queues) et seuils élevés. |
| **Stockage SQL** | **Élevé** | Espace Disque & Index | Élagage des scores < 30% et archivage automatique. |
| **Consultation** | **Très Faible** | Lecture Simple | Dashboard basé sur un `SELECT` direct dans la table de matches pré-calculés. |

---

## 7. Compromis et Optimisations Critiques

Pour garantir la fluidité du système avec 10k+ utilisateurs sans trahir l'ADN du projet :

### Garantie d'Individualité
Le principe de **Dimension Humaine** impose une personnalisation totale :
1. **Pas de "Profil Type"** : Chaque calcul de score est propre à l'utilisateur actuel.
2. **IA Sacralisée** : L'analyse Layer 2 (IA) est strictement individuelle. Aucun résultat d'IA n'est partagé entre deux utilisateurs.

### Optimisations de Performance Validées
| Choix Technique | Description |
| :--- | :--- |
| **Persistance (Store-First)** | Les scores Layer 1 sont calculés par le Pull Worker et écrits en dur. Le dashboard ne fait aucun calcul. |
| **Lazy Invalidation** | Si l'utilisateur change son profil, ses anciens scores sont marqués `stale`. Ils sont recalculés en arrière-plan (priorité aux offres récentes). |
| **Élagage (Pruning)** | On ignore les scores < 30%. Économie de ~90% de l'espace disque. |
| **Futur : In-Memory (Redis)** | Pour les montées en charge extrêmes, utilisation de Redis Sets pour les intersections de compétences. |

### Calcul Complet par Soustraction
Contrairement à une logique de circuit-court strict, le système calcule le score d'attractivité complet pour chaque offre détaillée. Chaque contrainte de l'utilisateur (métier refusé, compétences proscrites, permis manquants) est traitée comme un point de friction qui soustrait un nombre de points défini au score de base, sans interrompre prématurément le calcul.

---

## 8. Activation du Matching (Seuil de Complétion)

Pour éviter les calculs inutiles et les résultats décevants, le moteur de matching reste "en sommeil" pour un utilisateur tant que son profil n'a pas atteint un seuil de maturité critique.

### Critères de réveil du moteur :
- **Identification** : Au moins un **Métier préféré** (ROME) doit être enregistré.
- **Socle Technique** : Un minimum de **5 compétences** doit être lié au profil.
- **Zone Géographique** : Le code postal et le rayon de mobilité doivent être renseignés.

### Comportement lors de l'Onboarding :
1. **Démarrage à froid** : Dès que les critères sont réunis, le système planifie un calcul massif du Layer 1 uniquement pour les offres **actives** (non archivées).
2. **Priorisation** : Les offres les plus récentes et celles correspondant aux métiers préférés sont traitées en premier.
3. **Optimisation** : Les offres passées (déjà archivées lors de l'inscription) ne sont jamais matchées pour le nouvel utilisateur afin de préserver les ressources.
