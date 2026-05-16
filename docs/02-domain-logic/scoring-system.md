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
Ce sont les éléments qui réduisent l'intérêt de l'offre, sans pour autant l'interdire totalement (Philosophie de la "Motivation par le rêve") :
*   **Métier Refusé** : -5 pts.
*   **Compétence Refusée (JIT)** : -1 pt par compétence.
*   **Permis Requis manquant** : -5 pts. (Permet au candidat de voir qu'un job idéal est accessible s'il passe son permis).
*   **Langue Requise manquante** : -5 pts.
*   **Contrat non souhaité** : -1 pt.

*Note : Ces handicaps sont lourds mais non-létaux. Une offre avec un handicap reste visible si sa pertinence sémantique (Niveau 1) est exceptionnelle.*

#### B. Les Filtres de Confort (Localisation)

La distance n'est pas traitée comme un simple chiffre, mais comme un **coût de confort quotidien** qui impacte l'attractivité de l'offre. Le calcul prend en compte le domicile du candidat et son **rayon de mobilité souhaité (R)**.

*   **Pénalité de Distance** : Soustraction de **0 à 5 pts**.
*   **Courbe de Friction (Logique du "Pivot Radius")** :
    *   **Proximité Immédiate (0 km)** : Pénalité de **0 pt**.
    *   **Au Rayon Souhaité (d = R)** : Pénalité de **1 pt** (perte de 50% de l'attractivité de confort).
    *   **Au-delà du Rayon (d > R)** : La pénalité continue d'augmenter vers le maximum (5 pts) de façon dégressive.

**La formule mathématique cible :**
La pénalité est nulle dans la "zone de gratuité" (`free_radius` = 5 km). Au-delà, elle s'applique avec une constante K pour qu'à la limite du rayon `R`, la pénalité vaille `penalty_at_radius` (1 pt).

$$DistanceEffective = max(0, Distance - 5)$$
$$Pénalité = 5 \times \frac{DistanceEffective}{DistanceEffective + K}$$

*   **Sécurité** : Si le rayon (R) n'est pas défini par l'utilisateur, une valeur par défaut de **10 km** (avec une zone de gratuité de 5 km) est utilisée pour le calcul.
*   **Bonus Télétravail** : Si le job mentionne le télétravail (détection par mots-clés), la pénalité de distance est **divisée par 2**.


#### C. Les Bonus d'Affinité (Légers)
Pour faire émerger les "coups de cœur" :
*   **Métier Favori** : +2 pts.
*   **Compétence Validée (Active)** : **0 pt**. Un léger bonus si l'offre demande quelque chose que l'utilisateur aime/maîtrise déjà.

#### D. La Fraîcheur (Vétusté)
*   **Malus de Vétusté** : -0.03 pt par jour après le 14ème jour de publication (plafonné à -1 pt).

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

*   **Philosophie** : Le score sémantique représente le **potentiel maximum** de l'offre (Pertinence métier). Le pré-score représente le **pourcentage d'acceptation** (Confort/Conditions).
*   **Plafonnement** : Le score d'attractivité est strictement compris entre **0 et 100**.
*   **Exception de données pauvres** : Si une offre ne contient aucune donnée technique exploitable (compétences/langues vides), l'attractivité est neutre (pas de malus de manque) pour laisser toute la place au score sémantique.

---

## 5. Logique de Tri et Présentation

L'ordre d'affichage sur le dashboard suit deux philosophies distinctes selon l'objectif de l'utilisateur :

### A. Onglet "Top Matches" (Le Réalisme)
*   **Tri** : Par `Score_Final` décroissant.
*   **Objectif** : Proposer les offres qui ont le meilleur équilibre entre tes compétences et tes contraintes de vie. C'est l'onglet prioritaire pour postuler.

### B. Onglet "Potentiel / Pépites" (La Découverte)
*   **Tri** : Par `Score_Sémantique` décroissant.
*   **Objectif** : Faire émerger les offres qui correspondent parfaitement à ton métier et ton "vibe", même si elles ont un faible score de confort (ex: trop loin, manque un permis). 
*   **Utilité** : Permet de ne pas rater une opportunité exceptionnelle qui pourrait te faire reconsidérer tes contraintes (ex: "Ce job est tellement parfait que je suis prêt à déménager ou à passer mon permis").

---

## 6. Représentation Visuelle (UI/UX)

Pour respecter l'inversion du regard, l'interface ne doit pas afficher un score unique "froid", mais deux dimensions :

### A. Le Double Indicateur
Sur chaque carte d'offre, on affiche :
*   **La Jauge de Pertinence (Bleue)** : Représente le Level 1 (Sémantique). Label : "Potentiel Métier".
*   **La Jauge de Confort (Verte)** : Représente le Level 2 (Attractivité). Label : "Indice de Confort".

### B. Le Breakdown "Pourquoi ce score ?"
Un clic sur le score d'attractivité ouvre un détail listant les frictions et bonus :
*   `[-] 15 pts` : Trop loin (25 km).
*   `[-] 10 pts` : Permis B requis non possédé.
*   `[+] 10 pts` : Métier favori.
*   `[+] 3 pts`  : 3 compétences maîtrisées sur cette offre.

### C. Feedback JIT (Just-In-Time)
Les badges de compétences sur l'offre sont interactifs :
*   **Badge Neutre** : Compétence demandée mais inconnue de l'utilisateur.
*   **Badge Vert** : Compétence que l'utilisateur possède (Bonus +1).
*   **Badge Rouge** : Compétence que l'utilisateur a refusée de pratiquer (Malus -5).
*   **Action** : Un clic permet de basculer l'état et de recalculer le score instantanément.

---

## 7. Roadmap d'Implémentation

### Phase 1 : Cœur du Système (Backend)
- [x] Création de la configuration centralisée (`config/matching.php`).
- [x] Refonte du `MatchingService` (Logique soustractive).
- [x] Intégration du Modèle Multiplicatif (Sémantique x Attractivité).
- [x] Mise en place du calcul de distance réel (Haversine) basé sur les coordonnées postales.
- [x] Injection du malus de vétusté automatique.

### Phase 2 : Interface & Interaction (Frontend)
- [x] Mise à jour du Dashboard pour afficher les deux jauges (Pertinence vs Confort).
- [x] Création du composant de détail du score (Pop-over/Tooltip).
- [x] Implémentation des endpoints API pour le feedback JIT (Skills status update).
- [x] Ajout de l'animation de recalcul de score lors d'un feedback.

### Phase 3 : Intelligence (Niveau 3)
- [x] Trigger de l'analyse IA (Gemini) restreint au Top 20 final.
- [x] Affichage de l'analyse narrative ultra-concise (3 lignes).

---

## 8. Onboarding et Pédagogie (First-Time Experience)

Le système de scoring étant inhabituel (inversion du regard), il nécessite une pédagogie active pour ne pas être perçu comme une "boîte noire".

### A. La Métaphore du GPS
Dès la première visite, l'utilisateur doit comprendre :
*   **Jauge Bleue (Pertinence)** = La destination (Est-ce que c'est le bon métier ?).
*   **Jauge Verte (Confort)** = La route (Est-ce que le trajet et les conditions me conviennent ?).

### B. Gamification de la Maturité
Afficher un statut de confiance de l'algorithme sur le profil :
*   **Niveau 1 : Apprenti** (Profil < 30%) : "Je découvre vos envies."
*   **Niveau 2 : Assistant** (30-70%) : "Je commence à filtrer le bruit pour vous."
*   **Niveau 3 : Expert** (> 70%) : "Je connais vos limites et vos ambitions par cœur."

### C. Feedback de Contrôle (Effet miroir)
Chaque interaction JIT (Just-In-Time) doit produire un effet visuel immédiat :
1.  L'utilisateur clique sur "Refuser une compétence".
2.  Une notification discrète apparaît : *"Compris ! Je pénaliserai désormais les offres exigeant cette compétence."*
3.  La jauge de confort de l'offre actuelle se met à jour avec une petite animation de descente.

### D. Le "Conseil de l'IA" sur les Handicaps
Au lieu de simplement soustraire des points, le détail du score doit être formulé de manière positive :
*   *Au lieu de* : "-10 pts : Pas de permis".
*   *Afficher* : "Ce job est une pépite pour vous ! Seul bémol : le permis est requis. Un défi à relever ?"
