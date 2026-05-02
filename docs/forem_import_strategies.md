# Stratégies d'Importation et de Synchronisation Forem

Ce document détaille les pistes pour gérer l'importation massive d'offres depuis le Forem (>40 000 entrées) tout en détectant les dépublications sans risquer le bannissement.

## 1. Le Problème
*   **Volume** : > 40 000 offres actives.
*   **Rate Limiting** : Risque de blocage si trop de requêtes (surtout pour les détails).
*   **Détection des suppressions** : Absence de flux "push" (webhooks).

---

## 2. Stratégies de Synchronisation (Scan)

### A. La Super-Pagination - [TESTÉ ET VALIDÉ ✅]
L'API du Forem accepte un paramètre `row` allant jusqu'à **1000**. C'est la méthode la plus rapide et la plus sûre pour scanner de gros volumes.

### B. Le Scan en Cycle Continu 🔄
*   **Principe** : Un Job tourne toutes les 15 minutes et récupère **une page tournante** (1, 2...) de 1000 offres.
*   **Détection de fin de cycle** : Le curseur est réinitialisé à 1 si `(page * 1000) >= total_offres` ou si la page renvoyée est vide.
*   **Archivage** : Si une offre n'est plus vue pendant 24h, elle passe en `status = 'archived'`. Elle peut être "ressuscitée" si elle réapparaît plus tard.

### C. Le Scan "Flash" (Nouveautés) ⚡
*   **Principe** : Récupérer uniquement la **Page 1** (`row=100`) toutes les 5 minutes pour découvrir les nouveautés urgentes.

---

## 3. Récupération des Détails Complets (Deep Sync)

### A. Architecture "Pull Worker" (Le moteur intelligent)
Au lieu de charger des milliers de jobs en queue, on utilise un worker unique (commande Laravel) qui tourne en boucle et "tire" (pull) l'offre la plus pertinente à traiter selon un tri dynamique :

1.  **Priorité 1 : Nouvelles offres** (`is_detailed = false`). 
    *   Tri : `last_seen_at DESC` (On veut le détail des plus fraîches en premier).
2.  **Priorité 2 : Maintenance** (`is_detailed = true`).
    *   Tri : `detailed_at ASC` (On rafraîchit les plus anciennes).

**Exception** : Le clic utilisateur déclenche toujours un Job "Push" immédiat (Priorité Haute).

### B. Smart Refresh (Mises à jour réactives)
Si le scan détecte un changement de date de `publication` ou de `fin`, l'offre est automatiquement repassée en `is_detailed = false`, ce qui la fait remonter instantanément en tête de pile du Pull Worker.

### C. Cadence et Politesse
*   Le Pull Worker respecte un délai de **3 à 10 secondes** entre chaque appel API.
*   Cela garantit un flux constant, prévisible et indétectable par les systèmes anti-bot.

---

## 4. Gestion de la Cohérence IA (Invalidation des Matchs)

### A. Le "Content Hash"
On stocke une empreinte numérique (MD5/SHA1) des champs critiques. Si le nouveau hash != l'ancien : **Invalidation immédiate** du score IA.

### B. Invalidation par Profil
Si un utilisateur met à jour son profil : Invalidation de tous ses scores de matching.

### C. Archivage et Résurrection
Les scores des offres archivées sont conservés. Si l'offre réapparaît identique, le score est réactivé sans nouvel appel IA.

---

## 5. Stratégie Hybride Optimale

| Tâche | Fréquence | Technique | Impact API |
| :--- | :--- | :--- | :--- |
| **Nouveautés** | 5 min | Scan Page 1 (`row=100`) | Très faible |
| **Nettoyage** | 15 min | Cycle Page X (`row=1000`) | Très faible |
| **Détails** | Continu | Pull Worker (1 req / 3-10s) | Constant |

---

## 6. Architecture Technique Suggérée

1.  **Status** : `active`, `archived`.
2.  **Champs dates** : `last_seen_at`, `detailed_at`, `publication`, `fin`.
3.  **Champ IA** : `content_hash`.
4.  **Upsert** : Indispensable pour le traitement massif des scans.
5.  **Indexation SQL** : Créer un index composite sur `(status, is_detailed, last_seen_at, detailed_at)` pour garantir des performances constantes sur le Pull Worker.
6.  **Rate Limiter** : Brider les appels via Laravel RateLimiter.
