# Architecture de la Base de Données (Optimisée V2)

L'architecture est normalisée pour exploiter l'intégralité des identifiants (`code` et `guid`) fournis par l'API Forem.

## A. Tables de Référence (Taxonomies)

### Table `metiers`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | Code ROME (ex: `K220401-1`) |
| `guid` | String | Identifiant de recherche (ex: `d786a2cc-...`) |
| `label` | String | Libellé du métier |

### Table `skills`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | Code compétence (ex: `16214`, `05`) |
| `label` | String | Libellé |
| `type` | Enum | `hard` (compétences), `soft` (savoir-être) |

### Table `employers`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `id_forem` | String | ID issu de l'API (ex: `DAOUST`) |
| `label` | String | Nom de l'entreprise |
| `logo` | Text (Long) | Base64 ou URL du logo |
| `description` | Text | Description de l'entreprise |

### Table `sources`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | ID de la source (ex: `FOREM`, `Accent`) |
| `label` | String | Libellé (ex: "Le Forem") |

### Table `benefits`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | ID de l'avantage (ex: `primeDeNuit`) |
| `label` | String | Libellé (ex: "Prime de nuit") |

### Table `languages` & `permits`
(Idem V1 : Tables de référence avec `code` et `label`)

## B. Table `jobs` (Données de l'offre)
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `forem_ref` | String (Unique) | Numéro de l'offre |
| `title` | String | Titre de l'offre |
| `metier_id` | Foreign Key | Lien vers `metiers.id` |
| `employer_id` | Foreign Key | Lien vers `employers.id` |
| `source_id` | Foreign Key | Lien vers `sources.id` |
| `description` | Text | Contenu HTML complet |
| `contract_type` | String | ex: "Durée déterminée" |
| `working_regime` | String | ex: "Temps partiel" |
| `location` | String | Ville ou région |
| `experience_label`| String | ex: "Moins de 2 ans" |
| `education_level` | String | ex: "Nettoyage locaux" |
| `contact_email` | String | Email pour postuler |
| `apply_instructions`| Text | Instructions |
| `expires_at` | Date | Fin de publication |
| `raw_data` | JSON | Réponse brute complète |

## C. Tables de Liaison
- **`job_skill`** : `job_id`, `skill_id`, `is_required` (Boolean)
- **`job_language`** : `job_id`, `language_id`, `level` (String), `is_required` (Boolean)
- **`job_permit`** : `job_id`, `permit_id`, `is_required` (Boolean)
- **`job_benefit`** : `job_id`, `benefit_id`

## D. Matching & Utilisateurs
(Idem V1 : `user_skills`, `user_languages`, `user_matches`)
