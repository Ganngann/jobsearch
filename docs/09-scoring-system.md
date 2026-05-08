# Système de Scoring : L'Attractivité de l'Offre (Postuler vs Recruter)

Ce document définit la philosophie de matching du projet. Contrairement aux systèmes classiques de recrutement qui évaluent le candidat, notre système évalue **l'attractivité d'une offre pour l'utilisateur**.

## 1. Philosophie : Inversion du Regard

Le scoring n'est pas un outil de sélection pour l'employeur, mais un **outil d'aide à la décision pour le candidat**. 

*   **Recruter** : Cherche si le candidat est "assez bon" pour l'entreprise.
*   **Postuler** : Cherche si l'entreprise est "assez pertinente" pour le projet de vie du candidat.

En conséquence, le système adopte une approche de **"Présomption de compatibilité"**. Toute offre sémantiquement proche est considérée comme excellente (100%) jusqu'à preuve du contraire (Veto ou Handicap).

## 2. Le Scoring par Soustraction (Attractivité)

Le Pré-score technique fonctionne par retrait de points d'attractivité. On ne cherche pas à savoir si le candidat a "coché toutes les cases", mais s'il existe des "points de friction" gênants.

### Structure du calcul (Base 100)

On part d'un score d'attractivité de **100 points** par défaut, puis on soustrait :

#### A. Les Handicaps Lourds (Soft Vetos)
Ce sont les éléments qui réduisent fortement l'intérêt de l'offre, sans pour autant l'interdire totalement :
*   **Métier Refusé** : -20 pts.
*   **Compétence Refusée (JIT)** : -5 pts par compétence.
*   **Permis Requis manquant** : -10 pts.
*   **Langue Requise manquante** : -10 pts.

*Note : Ces handicaps sont lourds mais non-létaux. Une offre "refusée" peut rester visible si sa pertinence sémantique (Niveau 1) est exceptionnelle (ex: le job de rêve dans la mauvaise catégorie).*

#### B. Les Filtres de Confort (Localisation)

La distance n'est pas traitée comme un simple chiffre, mais comme un **coût de confort quotidien** qui impacte l'attractivité de l'offre. Le calcul prend en compte le domicile du candidat et son **rayon de mobilité souhaité (R)**.

*   **Pénalité de Distance** : Soustraction de **0 à 30 pts**.
*   **Courbe de Friction (Logique du "Pivot Radius")** :
    *   **Proximité Immédiate (0 km)** : Pénalité de **0 pt**.
    *   **Au Rayon Souhaité (d = R)** : Pénalité de **15 pts** (perte de 50% de l'attractivité de confort).
    *   **Au-delà du Rayon (d > R)** : La pénalité continue d'augmenter vers le maximum (30 pts) de façon dégressive.

**La formule mathématique cible :**
$$Pénalité = 30 \times \frac{Distance}{Distance + Rayon}$$

*Note : Cette approche est plus sensible sur les premiers kilomètres (changement de mode de transport) et s'aplatit au-delà (une fois en voiture, 5km de plus impactent moins le ressenti).*


#### C. Les Bonus d'Affinité (Légers)
Pour faire émerger les "coups de cœur" :
*   **Métier Favori** : +10 pts.
*   **Compétence Validée (Active)** : **+1 pt**. Un léger bonus si l'offre demande quelque chose que l'utilisateur aime/maîtrise déjà.

## 3. L'Entonnoir à trois niveaux (Rappel)

### Niveau 1 : Le Score Sémantique (Fond)
*   **Rôle** : Identifie la "vibe" et la proximité de concept. 
*   **Philosophie** : C'est le moteur de découverte. Il ignore les fautes d'orthographe ou les variations de libellés des compétences.

### Niveau 2 : Le Pré-Score (Forme & Limites)
*   **Rôle** : Applique les filtres de répulsion décrits ci-dessus.
*   **Mécanique JIT** : Permet à l'utilisateur de "dresser" l'algorithme en temps réel en qualifiant les compétences directement sur l'offre.

### Niveau 3 : Le Score IA (Analyse Narrative)
*   **Rôle** : Réservé au Top 20 des offres les plus "attractives" (Niveaux 1 + 2).
*   **Philosophie** : L'IA valide si, au-delà des chiffres, l'histoire du candidat résonne avec le besoin de l'entreprise.

---

## 4. Formule de calcul globale : Le Modèle Multiplicatif

Le classement du dashboard utilise le Pré-score comme un **coefficient d'atténuation** du score sémantique :

$$Score_{Final} = Score_{Sémantique} \times (\frac{Score_{Attractivité}}{100})$$

*   **Philosophie** : Le score sémantique représente le **potentiel maximum** de l'offre. Le pré-score représente le pourcentage de ce potentiel que l'utilisateur est prêt à accepter compte tenu de ses contraintes.
*   **L'Exception Sémantique** : Un job "refusé" (Attractivité 40%) qui est sémantiquement parfait (95%) obtiendra un score final de **38%**. Il ne sera pas dans le Top Match mais restera "trouvable" dans la découverte, permettant au candidat de reconsidérer sa position face à une opportunité exceptionnelle.
