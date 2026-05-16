# Stratégie de Vectorisation (Embeddings)

L'implémentation des vecteurs (embeddings) repose sur le modèle **Gemini Embedding 2**, offrant un matching sémantique multimodal performant.

## 1. Documents Textuels (Input)

Pour chaque entité, nous construisons une chaîne de texte brute exhaustive. L'objectif est d'inclure **absolument toutes les données disponibles**, y compris les métadonnées des tables liées, pour capturer le contexte maximum.

### Génération Indépendante (Asymmetric Retrieval)
Les vecteurs sont générés dans des appels API totalement séparés et stockés indépendamment. Gemini ne compare jamais les deux ; c'est notre serveur qui le fait. Cependant, nous utilisons des formats différents pour que Gemini les place dans le même espace sémantique :

- **Action A : Vectoriser une Offre** (Stockage en DB) :
  Format : `title: [Titre de l'offre] | text: [Contenu exhaustif]`
- **Action B : Vectoriser un Profil** (Stockage en DB) :
  Format : `task: search result | query: [Contenu exhaustif du profil]`

### Détails du Contenu exhaustif

#### Offre d'Emploi (`JobOffer`)
Contenu : `[Métier] | [Description] | [Compétences (Hard/Soft/Office)] | [Langues] | [Permis] | [Secteurs] | [Expériences requises] | [Études] | [Description Employeur]`

#### Profil Utilisateur (`User`)
Contenu : `[Headline] | [Aspirations] | [Profile Text] | [Compétences validées] | [Narrative Facts] | [Expériences] | [Formations] | [Projets] | [Certifications] | [Langues] | [Bénévolat] | [Centres d'intérêt]`

---

## 2. Architecture Technique

### GeminiService
Ajout d'une méthode `embed(string $text): array` :
- Modèle : `gemini-embedding-001` (Optimisation des coûts : 2x moins cher que v2).
- Limite : 2048 tokens (Amplement suffisant pour Offre/Profil).
- Configuration : `task_type: "RETRIEVAL_DOCUMENT"` pour les offres et `"RETRIEVAL_QUERY"` pour les candidats.
- **Important** : Pour une dimension de 768, une **normalisation manuelle** du vecteur est requise avec ce modèle.
- Retourne : Un tableau de flottants (vecteur).

> [!TIP]
> Si nous passons sur Gemini Embedding 2 plus tard, l'utilisation du **Batch API** permettra de réduire les coûts de 50%.

### Stockage
- Colonne `vector_embedding` (Type : `vector` ou `json`) dans les tables `job_offers` et `users`.
- Migration requise.

### VectorService (Orchestration)
- `updateJobVector(JobOffer $job)` : Déclenché après `syncFullDetails`.
- `updateUserVector(User $user)` : Déclenché après validation d'un fait ou modification du profil.

---

## 3. Matching Sémantique Local (Layer 1)

Contrairement à l'analyse narrative (Layer 3) qui nécessite un appel API par match, le matching sémantique s'effectue **entièrement en local** sur notre serveur.

### Fonctionnement
1. **Extraction** : On récupère le vecteur de l'utilisateur (Query) et les vecteurs des offres (Documents) depuis la base de données SQLite.
2. **Calcul Local** : On utilise une fonction PHP pour calculer la **Similitude Cosinus** entre les vecteurs.
3. **Tri** : Les offres sont classées par score de proximité (de -1 à 1, où 1 est une correspondance parfaite).

### Avantages
- **Performance** : Comparaison de milliers d'offres en quelques millisecondes.
- **Économie** : Zéro coût d'API lors de la phase de comparaison.
- **Confidentialité** : Le matching final reste sur nos serveurs.

## 4. Prochaines étapes
1. Migration SQL : ajout des colonnes d'embeddings.
2. Implémentation `GeminiService::embed`.
3. Création du `VectorService` et des Triggers.
4. Intégration du score sémantique dans `MatchingService`.
