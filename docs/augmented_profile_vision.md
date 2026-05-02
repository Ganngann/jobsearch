# Vision : Le Profil Narratif Augmenté

## 1. Objectif
Transformer le profil utilisateur d'une simple collection de compétences en une entité tridimensionnelle capable de générer des scores de matching "humains" ultra-précis. Encourager l'utilisateur à enrichir son profil par une visualisation claire des dimensions manquantes.

## 2. Architecture des Données

### Couche 1 : Parcours Professionnel (Données Structurées)
Données chronologiques et factuelles issues du CV.
*   **Expériences** : Postes, entreprises, dates, missions.
*   **Formation** : Diplômes, certifications, cursus.

### Couche 2 : Profil Narratif (Données Semi-Structurées)
Évolution du modèle `UserFact` actuel. Chaque fait est classé selon 4 axes :
*   **Valeurs & Principes** : Éthique, engagements, convictions (Ex: Écologie, intégrité).
*   **Objectifs & Aspirations** : Moteurs de carrière, ambitions (Ex: Apprendre, diriger, stabiliser).
*   **Soft Skills & Posture** : Compétences comportementales (Ex: Résilience, diplomatie, autonomie).
*   **Préférences & Conditions** : Besoins concrets d'environnement (Ex: Télétravail, management horizontal).

### Couche 3 : Synthèse de Matching (Analyse IA)
Profil de compatibilité dynamique généré par l'IA à partir des Couches 1 et 2.

## 3. Le Système de "Trous Narratifs"
Le système analyse la densité de chaque catégorie pour guider l'IA.

### Seuils de Complétude :
*   **Dimension Incomplète** : < 3 faits.
*   **Profil "Authentique"** : Minimum 12 faits (3 par catégorie).
*   **Seuil de Haute Définition** : 16 à 20 faits denses pour un matching précis.

### Répartition Cible (Le "Standard d'Or") :
Pour un matching optimal, le système vise un équilibre de **15 à 20 faits actifs** répartis comme suit :

1.  **Valeurs & Principes (4-5 faits)** : Socle éthique, engagements et lignes rouges (ex: Écologie, Intégrité). *Évite le conflit moral.*
2.  **Objectifs & Aspirations (3-4 faits)** : Moteurs de carrière et direction souhaitée (ex: Optimisation technique, Impact social). *Évite l'ennui.*
3.  **Savoir-être & Style (5-6 faits)** : Traits de caractère et posture pro (ex: Syndrome de l'imposteur comme levier, Aversion pour la routine). *Définit l'unicité.*
4.  **Environnement & Préférences (3-5 faits)** : Besoins concrets et cadre de travail (ex: Télétravail, Management horizontal). *Garantit le confort quotidien.*

## 4. Moteur de Stimulation Narrative (La Maïeutique)
L'IA Profile Builder ne doit plus seulement "extraire", elle doit "provoquer" le récit.

### Principes de conversation :
*   **Le Springboard (Le Rebond) :** Utiliser les données du parcours (Couche 1) comme point de départ. (Ex: "Pourquoi avoir quitté ce poste si vite ?").
*   **La Valorisation de l'Échec :** Présenter les difficultés comme des sources de Soft Skills. (Ex: "Comment ce conflit de valeurs a-t-il forgé votre posture actuelle ?").
*   **L'Empathie Réciproque :** Valider les émotions avant de catégoriser.
*   **Le Mirroring :** Renvoyer à l'utilisateur une image de lui-même pour l'inciter à l'approfondir.

### Indicateur de Profondeur (UI) :
Remplacer la complétude par la "Profondeur de Profil" :
1.  **Profil de Surface** (CV Seul).
2.  **Profil en Relief** (Savoir-être identifiés).
3.  **Profil Authentique** (Histoires et victoires partagées).

## 5. Évolutions Techniques Requises

### Modèles SQL à créer :
*   `Experience` : (user_id, company_name, job_title, start_date, end_date, description, is_current)
*   `Education` : (user_id, school_name, degree, field_of_study, graduation_year)

### Refonte de `UserFact` :
*   Migration pour forcer l'usage de l'une des 4 catégories (VALEURS, OBJECTIFS, SOFT_SKILLS, PREFERENCES).
*   Ajout d'une relation optionnelle entre un `UserFact` et une `Experience` (Ex: "J'ai appris la résilience lors de mon poste chez X").

## 6. Workflow Utilisateur
1.  **Import/Saisie du CV** : L'utilisateur remplit son parcours.
2.  **Analyse Initiale** : L'IA détecte les compétences et suggère des premiers "Facts".
3.  **Conversation Ciblée** : L'IA invite à discuter des zones d'ombre (Ex: "Votre parcours est impressionnant, mais qu'est-ce qui vous motive vraiment à changer aujourd'hui ?").
4.  **Matching Augmenté** : Le score final sur le dashboard prend en compte la compatibilité de "Savoir-être" (Soft Skills) et de "Préférences".

---
*Document de travail - Auteur : Morgan & Antigravity*
