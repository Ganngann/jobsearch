# Référence API Forem

Ce document recense les endpoints de l'API du Forem utilisés par le projet.

## 1. Recherche d'offres (Discovery)

- **URL** : `https://www.leforem.be/recherche-offres/api/Recherches/Search`
- **Méthode** : `GET`
- **Paramètres** : `page`, `row`
- **Exemple** : [search-results.json](./api-examples/search-results.json)

### Mapping Search → DB
| Champ API | Type | Colonne DB | Notes |
| :--- | :--- | :--- | :--- |
| `id` | Integer | `jobs.forem_id` | ID numérique unique |
| `numero` | String | `jobs.forem_ref` | Même valeur que `id` en string |
| `titre` | String | `jobs.title` | — |
| `nomEmployeur` | String | `employers.label` | → FK `employer_id` |
| `lieuxTravail` | Array | `jobs.location` + `locations_json` | `location` = 1er élément |
| `secteursActivite` | Array | → pivot `job_sector` | — |
| `logo` | UUID | `employers.logo_uuid` | **Pas** les données du logo |
| `isPostulable` | Boolean | `jobs.is_postulable` | Absent parfois → défaut `false` |
| `email` | String | `jobs.contact_email` | Peut être absent |
| `telephone` | String | `jobs.contact_phone` | **Uniquement dans Search** |
| `nombrePostes` | Integer | `jobs.nombre_postes` | — |
| `debut` | ISO 8601 | `jobs.published_at` | Format fiable |
| `fin` | ISO 8601 | `jobs.expires_at` | Format fiable |

> **`id` vs `numero`** : On stocke les deux. `forem_id` (Integer) pour les appels API, `forem_ref` (String) pour l'affichage.

## 2. Détail de l'offre (Detail)

- **URL** : `https://www.leforem.be/recherche-offres/api/Diffusion/DetailOffre/{id}`
- **Méthode** : `GET`
- **Exemple** : [job-detail.json](./api-examples/job-detail.json)

### Mapping Detail → DB
| Champ API | Type | Colonne DB | Notes |
| :--- | :--- | :--- | :--- |
| `descriptionJob` | HTML | `jobs.description` | Source principale pour l'IA |
| `competencies` | Array | → pivot `job_skill` (hard) | `{code, libelle, required}` |
| `softSkills` | Array | → pivot `job_skill` (soft) | `{code, libelle, required}` |
| `metier` | String | → `metiers.label` | Simple label, pas de code ROME |
| `experience` | Array | → pivot `job_experience` | `{code, libelle, required, experience}` — code = ROME |
| `etudes` | Array | → pivot `job_study` | `{libelle}` — **tableau** |
| `langues` | Array | → pivot `job_language` | `{code, libelle, experience}` avec niveau |
| `permisConduire` | Array | → pivot `job_permit` | `{code, libelle, valeur}` |
| `shift.hours` | String | `jobs.working_hours` | Parser string → float |
| `shift.shiftPeriod` | String | `jobs.shift_period` | Texte libre |
| `benefits.basePay` | String | `jobs.base_salary` | — |
| `benefits.comments` | HTML | `jobs.benefits_comments` | Prolongation, etc. |
| `positionDateInfo.startDate` | String | `jobs.start_date` | Format `DD/MM/YYYY` |
| `howToApply.*` | Object | `jobs.contact_*` | Nom, email, instructions |
| `logoEmployeur` | Base64 | `employers.logo_base64` | Données brutes |
| `logoMimeType` | String | `employers.logo_mime_type` | ex: `image/png` |

### ⚠️ Pièges de parsing

#### Formats de dates incohérents
| Source | Champ | Format | Exemple |
| :--- | :--- | :--- | :--- |
| Search | `debut` / `fin` | ISO 8601 | `2026-04-30T00:00:00+02:00` |
| Detail | `dateDebutDiffusion` | Date FR | `30/04/2026` |
| Detail | `datePublication` | Label humain | `"Publié aujourd'hui"` |
| Detail | `positionDateInfo.startDate` | Date FR | `01/06/2026` |

**Stratégie** : Utiliser les dates ISO de Search pour `published_at`/`expires_at`. Le `datePublication` du Detail est un label humain inutilisable.

#### Logo : UUID (Search) vs Base64 (Detail)
1. **Search** : Stocker `logo_uuid` dans `employers`
2. **Detail** : Enrichir avec `logo_base64` + `logo_mime_type`

#### Champ `telephone` uniquement dans Search
Le téléphone n'existe que dans Search. Le capturer lors de la sync initiale.

## 3. Statistiques par critères (Facettes)

- **URL** : `https://www.leforem.be/recherche-offres/api/Recherches/SearchNombreParCritere`
- **Méthode** : `POST`
- **Paramètres** : `page`, `row`
- **Exemple** : [search-by-criteria.json](./api-examples/search-by-criteria.json) *(tronqué)*

### Catégories disponibles
| Filtre | Utilité projet |
| :--- | :--- |
| `Metier` | Alimente `metiers.guid` pour recherche ciblée |
| `ContratTravail` | Référence des types de contrats |
| `Language` | Liste complète des langues |
| `Certification` | Brevets et certifications |
| `Drivers License` | Types de permis |
| `Activite` | Secteurs d'activité |

> **Usage principal** : Récupérer les `guid` des métiers et pré-alimenter les tables de référence au bootstrap.
