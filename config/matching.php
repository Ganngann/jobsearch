<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Système de Scoring (Attractivité)
    |--------------------------------------------------------------------------
    |
    | Ces valeurs définissent comment le score d'attractivité d'une offre est
    | calculé par rapport au profil du candidat.
    |
    */

    'base_score' => 100,

    'handicaps' => [
        'refused_metier'   => 5,
        'refused_skill'    => 1,
        'missing_permit'   => 5,
        'missing_language' => 5,
        'contract_mismatch' => 1,
    ],

    'bonuses' => [
        'favorite_metier' => 2,
        'active_skill'    => 0,
    ],

    'location' => [
        'max_penalty'       => 5,
        'default_radius'    => 10,
        'free_radius'       => 5,
        'penalty_at_radius' => 1,
    ],

    'freshness' => [
        'malus_per_day'  => 0.03,
        'start_after_days' => 14,
        'max_malus'      => 1,
    ],

    'semantic' => [
        'min_threshold' => 0,
    ],
];
