# Architecture de la Base de Données (V4 - Corrigée)

L'architecture est normalisée pour exploiter l'intégralité des données fournies par l'API Forem (Search, Detail, Facettes).

## A. Tables de Référence (Taxonomies)

### Table `metiers`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | Code ROME issu de `experience[].code` (ex: `K220401-1`) |
| `guid` | String (Nullable) | GUID issu des Facettes pour la recherche (ex: `d786a2cc-...`) |
| `label` | String | Libellé du métier |

> **Note** : Le champ `metier` du Detail API retourne un simple label (string).
> Le `code` ROME provient du tableau `experience[].code`.
> Le `guid` est obtenu via l'endpoint Facettes (SearchNombreParCritere) en faisant correspondre le `libelle`.

### Table `skills`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String | Code compétence (ex: `16214` pour hard, `05` pour soft) |
| `label` | String | Libellé |
| `type` | Enum | `hard` (competencies), `soft` (softSkills) |

### Table `sectors`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `label` | String (Unique) | Libellé du secteur d'activité |

### Table `employers`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `id_forem` | String (Nullable) | ID issu de l'API (ex: `DAOUST`) |
| `label` | String | Nom de l'entreprise |
| `logo_uuid` | String (Nullable) | UUID du logo (issu de Search, ex: `51207d27-...`) |
| `logo_base64` | Text (Nullable) | Données Base64 du logo (issu de Detail) |
| `logo_mime_type` | String (Nullable) | Type MIME du logo (ex: `image/png`, issu de Detail) |
| `description` | Text (Nullable) | Description HTML de l'entreprise |

> **Stratégie Logo** : L'endpoint Search retourne un UUID, l'endpoint Detail retourne le Base64 + mimeType.
> Au premier passage (Search), on stocke `logo_uuid`. Lors de l'appel Detail, on enrichit avec `logo_base64` et `logo_mime_type`.

### Table `sources`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String (Unique) | ID de la source (ex: `FOREM`, `Accent`) |
| `label` | String | Libellé (ex: "Le Forem") |

### Table `benefits`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String (Unique) | ID de l'avantage (ex: `primeDeNuit`) |
| `label` | String | Libellé (ex: "Prime de nuit") |

### Table `languages`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String (Unique) | Code ISO de la langue (ex: `FR`, `NL`, `EN`) |
| `label` | String | Libellé (ex: "Français") |

### Table `permits`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `code` | String (Unique) | Code du permis (ex: `5101005`) |
| `label` | String | Libellé complet (ex: "Permis de conduire validé pour une catégorie B") |
| `value` | String | Catégorie courte (ex: `B`, `C`, `CE`) |

### Table `studies`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `label` | String (Unique) | Libellé de l'étude/formation (ex: "Nettoyage locaux") |

> L'API Detail retourne un **tableau** `etudes[]`, chaque entrée n'ayant qu'un `libelle`.

## B. Table `job_offers` (Données de l'offre)
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `forem_id` | Integer (Unique) | ID numérique de l'offre (`id` dans Search) |
| `forem_ref` | String (Unique) | Numéro de l'offre (`numero`, string) |
| `title` | String | Titre de l'offre |
| `metier_id` | Foreign Key (Nullable) | Lien vers `metiers.id` |
| `employer_id` | Foreign Key | Lien vers `employers.id` |
| `source_id` | Foreign Key (Nullable) | Lien vers `sources.id` |
| `description` | Text | Contenu HTML complet (`descriptionJob`) |
| `contract_type` | String | ex: "Durée déterminée" |
| `working_regime` | String | ex: "Temps partiel" |
| `working_regime_detail` | String (Nullable) | ex: "Travail de jour" (`regimeTravailPrecision`) |
| `working_hours` | Decimal (Nullable) | ex: 22.48 (`shift.hours`) |
| `shift_period` | String (Nullable) | Horaires détaillés (`shift.shiftPeriod`) |
| `base_salary` | String (Nullable) | ex: "CP 329.02 Echelon 2" (`benefits.basePay`) |
| `benefits_comments` | Text (Nullable) | Commentaires avantages HTML (`benefits.comments`) |
| `nombre_postes` | Integer | Nombre de postes ouverts (défaut: 1) |
| `location` | String (Nullable) | Ville ou région (premier élément de `lieuxTravail`) |
| `locations_json` | JSON (Nullable) | Tableau complet des lieux de travail |
| `contact_name` | String (Nullable) | Nom du contact (`howToApply.prefferedGivenName` + `familyName`) |
| `contact_email` | String (Nullable) | Email pour postuler (`howToApply.email` ou `email` dans Search) |
| `contact_phone` | String (Nullable) | Téléphone (depuis Search : `telephone`) |
| `apply_instructions` | Text (Nullable) | Instructions HTML (`howToApply.comments`) |
| `is_postulable` | Boolean | Postulable via Forem (défaut: false) |
| `start_date` | Date (Nullable) | Date de début du poste (`positionDateInfo.startDate`, format `DD/MM/YYYY`) |
| `published_at` | DateTime (Nullable) | Date de mise en ligne (ISO 8601 depuis Search) |
| `expires_at` | Date (Nullable) | Fin de publication (ISO 8601 depuis Search) |
| `raw_data` | JSON (Nullable) | Réponse brute complète de l'API Detail |

> **Dates** : L'endpoint Search fournit des dates ISO 8601 (`2026-04-30T00:00:00+02:00`).
> L'endpoint Detail fournit des dates au format texte FR (`30/04/2026`) ou des labels humains (`"Publié aujourd'hui"`).
> **Stratégie** : Toujours privilégier les dates ISO de Search pour `published_at` et `expires_at`.
> Utiliser `positionDateInfo.startDate` (format `DD/MM/YYYY`) pour `start_date`, à parser manuellement.

## C. Tables de Liaison (Pivots)

### `job_offer_skill`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `skill_id` | Foreign Key | Lien vers `skills.id` |
| `is_required` | Boolean | Compétence obligatoire ? |

### `job_offer_language`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `language_id` | Foreign Key | Lien vers `languages.id` |
| `level` | String (Nullable) | Niveau requis (ex: "B2 - Avancé") |
| `is_required` | Boolean | Langue obligatoire ? (défaut: true) |

### `job_offer_permit`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `permit_id` | Foreign Key | Lien vers `permits.id` |
| `is_required` | Boolean | Permis obligatoire ? (défaut: true) |

### `job_offer_benefit`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `benefit_id` | Foreign Key | Lien vers `benefits.id` |

### `job_offer_sector`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `sector_id` | Foreign Key | Lien vers `sectors.id` |

### `job_offer_study`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `study_id` | Foreign Key | Lien vers `studies.id` |

> Remplace l'ancien champ `education_level` (string). L'API Detail retourne `etudes[]` comme tableau.

### `job_offer_experience`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `metier_id` | Foreign Key | Lien vers `metiers.id` (via `experience[].code`) |
| `is_required` | Boolean | Expérience obligatoire ? |
| `experience_label` | String (Nullable) | Durée requise (ex: "Moins de 2 ans") |

> Le champ `experience[]` de l'API Detail est un **tableau d'objets** contenant un code ROME (`code`), un libellé de métier (`libelle`), un flag `required`, et une durée d'expérience (`experience`). Ce n'est PAS un simple label.

## D. Utilisateurs & Matching

### Table `users`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `name` | String | Nom complet |
| `email` | String (Unique) | Adresse email |
| `password` | String | Mot de passe hashé |
| `profile_text` | Text (Nullable) | Profil textuel libre (envoyé à l'IA) |
| `location` | String (Nullable) | Localisation préférée |
| `created_at` | Timestamp | Date de création |
| `updated_at` | Timestamp | Date de mise à jour |

### Table `user_skill` (Pivot)
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `user_id` | Foreign Key | Lien vers `users.id` |
| `skill_id` | Foreign Key | Lien vers `skills.id` |
| `level` | Enum (Nullable) | Niveau : `beginner`, `intermediate`, `advanced`, `expert` |

### Table `user_language` (Pivot)
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `user_id` | Foreign Key | Lien vers `users.id` |
| `language_id` | Foreign Key | Lien vers `languages.id` |
| `level` | String (Nullable) | Niveau (ex: "B2 - Avancé") |

### Table `user_permit` (Pivot)
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `user_id` | Foreign Key | Lien vers `users.id` |
| `permit_id` | Foreign Key | Lien vers `permits.id` |

### Table `user_matches`
| Colonne | Type | Description |
| :--- | :--- | :--- |
| `id` | BigInt | Clé primaire |
| `user_id` | Foreign Key | Lien vers `users.id` |
| `job_offer_id` | Foreign Key | Lien vers `job_offers.id` |
| `pre_score` | Integer (Nullable) | Score Layer 1 (filtrage statique, 0–100) |
| `ai_score` | Integer (Nullable) | Score Layer 2 (analyse IA, 0–100) |
| `final_score` | Integer (Nullable) | Score combiné final (0–100) |
| `strengths` | JSON (Nullable) | Points forts identifiés par l'IA |
| `weaknesses` | JSON (Nullable) | Points faibles identifiés par l'IA |
| `ai_raw_response` | JSON (Nullable) | Réponse brute de Gemini |
| `analyzed_at` | Timestamp (Nullable) | Date de l'analyse IA |
| `created_at` | Timestamp | Date de création |
| `updated_at` | Timestamp | Date de mise à jour |

> **Contrainte unique** : `(user_id, job_offer_id)` — un utilisateur ne peut matcher qu'une seule fois par offre.
