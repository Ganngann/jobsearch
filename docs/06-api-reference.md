# Référence API Forem

Ce document recense les points d'entrée (endpoints) de l'API du Forem utilisés par le projet.

## 1. Recherche d'offres (Discovery)
Cet endpoint permet de lister les offres d'emploi avec des données résumées.

- **URL** : `https://www.leforem.be/recherche-offres/api/Recherches/Search`
- **Méthode** : `GET`
- **Paramètres** :
    - `page` : Numéro de la page.
    - `row` : Nombre de résultats par page.
- **Exemple de réponse** : [search-results.json](./api-examples/search-results.json)

### Analyse des champs clés
| Champ | Description | Usage dans le projet |
| :--- | :--- | :--- |
| `id` / `numero` | Identifiant unique de l'offre | Utilisé pour `forem_ref` dans la table `jobs`. |
| `titre` | Titre de l'annonce | Nom de l'offre. |
| `nomEmployeur` | Nom de l'entreprise | Champ `employer`. |
| `lieuxTravail` | Liste des lieux | Localisation. |
| `secteursActivite` | Secteurs concernés | Aide à la classification. |

## 2. Détail de l'offre (Detail)
Cet endpoint fournit toutes les informations nécessaires à l'analyse sémantique.

- **URL** : `https://www.leforem.be/recherche-offres/api/Diffusion/DetailOffre/{id}`
- **Méthode** : `GET`
- **Exemple de réponse** : [job-detail.json](./api-examples/job-detail.json)

### Champs cruciaux pour le Matching
| Champ | Description | Usage |
| :--- | :--- | :--- |
| `descriptionJob` | Texte complet de l'offre | Source principale pour l'IA (Gemini). |
| `competencies` | Liste des compétences techniques | Utilisé pour le calcul du Layer 1. |
| `softSkills` | Liste des savoir-être | Utilisé pour le calcul du Layer 1. |
| `experience` | Expérience requise | Filtrage et contexte IA. |
| `metier` | Libellé du métier cible | Classification. |

## 3. Statistiques par critères (Facettes)
Cet endpoint permet d'obtenir le nombre d'offres par critère (métier, région, etc.).

- **URL** : `https://www.leforem.be/recherche-offres/api/Recherches/SearchNombreParCritere`
- **Méthode** : `POST`
- **Paramètres** : `page`, `row`
- **Exemple de réponse** : [search-by-criteria.json](./api-examples/search-by-criteria.json)
