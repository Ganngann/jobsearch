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
        'refused_metier'   => 20,
        'refused_skill'    => 5,
        'missing_permit'   => 10,
        'missing_language' => 10,
    ],

    'bonuses' => [
        'favorite_metier' => 10,
        'active_skill'    => 1,
    ],

    'location' => [
        'max_penalty'    => 30,
        'default_radius' => 30,
    ],

    'freshness' => [
        'malus_per_day'  => 0.5,
        'start_after_days' => 14,
        'max_malus'      => 10,
    ],

    'semantic' => [
        'min_threshold' => 0.6,
    ],
];
