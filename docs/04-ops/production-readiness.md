# Rapport de Préparation à la Production (Production Readiness Report)

Ce document liste les éléments de la plateforme qui nécessitent des ajustements, des corrections ou des implémentations manquantes avant un passage en production.

## 1. Algorithme de Matching (`MatchingService` / `JobMatcherService`)

**Problèmes identifiés :**
- L'algorithme de calcul du score (`JobMatcherService::matchLocation`) indique qu'il doit utiliser un vrai calcul de distance. Actuellement, le code utilise un mock simple basé sur la comparaison de code postal ou utilise une logique basique. Un "TODO" existe dans le code : `TODO: Intégrer un calcul de distance réel via API ou table de distances`. Bien que `MatchingService` utilise `calculateDistance` (Formule de Haversine), `JobMatcherService` semble être une version parallèle ou obsolète qui devrait être soit supprimée soit mise à jour.
- Le constructeur de `MatchingService` a été modifié pour requérir un `VectorService`, mais certains tests (`MatchingServiceTest`) n'avaient pas été mis à jour pour refléter ce changement (corrigé).
- La structure de retour de `calculatePreScore` de `MatchingService` a été changée d'une logique basée sur des pourcentages par catégories à une logique soustractive basée sur 100 avec des `penalties` et `bonuses`. Les tests unitaires n'étaient pas à jour par rapport à cette nouvelle logique (corrigé).

**Pistes de solutions :**
- Homogénéiser les services de matching : Déterminer si `JobMatcherService` est déprécié au profit de `MatchingService`. Si oui, nettoyer la base de code pour supprimer `JobMatcherService`.
- Intégrer un service de calcul de distance réel ou utiliser systématiquement la formule de Haversine avec une base de données de coordonnées postales fiable et testée.

## 2. Incohérences Frontend (Alpine.js & Tests)

**Problèmes identifiés :**
- **Mobility Component (`resources/js/mobility.js`) :** La méthode asynchrone `save` n'était pas correctement gérée dans les tests Vitest (problème d'asynchronisme de la file d'attente d'événements JavaScript), ce qui faisait échouer le test vérifiant l'affichage du message de succès.
- **Feedback Component (`resources/js/feedback.js`) :** Le composant gère les erreurs de requêtes réseau en affichant un log en console (`console.error`), mais les tests tentaient d'intercepter une alerte `window.alert` qui n'a jamais été implémentée.
- **Discovery Component (`resources/js/discovery.js`) :** L'importation globale d'`alpinejs` causait des conflits (`MutationObserver is not defined`) dans l'environnement de test Vitest `happy-dom`. Il fallait isoler le mock de la librairie.

**Pistes de solutions :**
- S'assurer que le composant de feedback affiche un retour visuel à l'utilisateur (un toast ou un message d'erreur en UI) au lieu d'un simple `console.error` qui passera inaperçu en production.
- Mettre en place un système de notification global pour toutes les actions asynchrones (succès/erreur).
- Gérer l'état de chargement lors des appels API (ex: désactiver les boutons pendant l'enregistrement).

## 3. Dépendances et Environnement

**Problèmes identifiés :**
- L'environnement de test Node (`happy-dom`) n'a pas accès à certaines API natives du navigateur (comme `MutationObserver`), ce qui nécessite des mocks spécifiques ou un paramétrage plus robuste.
- Le fichier `vitest.config.js` est absent ou mal configuré par rapport aux besoins du projet (les tests passaient avec une configuration générique depuis le `package.json` ou `vite.config.js` mais n'étaient pas robustes face aux imports de bibliothèques tierces comme Alpine).

**Pistes de solutions :**
- Créer un fichier de setup (ex: `tests/setup.js`) pour initialiser l'environnement de test avec tous les mocks nécessaires (`fetch`, API de navigateur, etc.).

## 4. Documentation

**Problèmes identifiés :**
- Certains documents mentionnés dans la roadmap de matching ou prévus par la logique ne sont pas complétés ou sont désynchronisés du code actuel.

**Pistes de solutions :**
- Synchroniser l'architecture documentée dans `docs/09-scoring-system.md` avec l'implémentation finale pour s'assurer que les scores calculés correspondent aux attentes métier.
