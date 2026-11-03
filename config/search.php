<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search Synonyms & Intent Mapping
    |--------------------------------------------------------------------------
    |
    | Maps natural buyer keywords to tags, categories, or text patterns.
    |
    */
    'synonyms' => [
        'dingin' => [
            'flavor_tags' => ['segar'],
            'categories' => ['Minuman'],
            'name_terms' => ['es'],
        ],
        'seger' => [
            'flavor_tags' => ['segar'],
            'categories' => ['Minuman'],
            'name_terms' => ['es'],
        ],
        'es' => [
            'flavor_tags' => ['segar'],
            'categories' => ['Minuman'],
            'name_terms' => ['es'],
        ],
        'panas' => [
            'flavor_tags' => ['hangat'],
            'name_terms' => ['panas', 'hangat'],
        ],
        'anget' => [
            'flavor_tags' => ['hangat'],
            'name_terms' => ['panas', 'hangat'],
        ],
        'hangat' => [
            'flavor_tags' => ['hangat'],
            'name_terms' => ['panas', 'hangat'],
        ],
        'pedes' => [
            'flavor_tags' => ['pedas'],
            'name_terms' => ['pedas', 'pedes', 'geprek', 'mercon'],
        ],
        'pedas' => [
            'flavor_tags' => ['pedas'],
            'name_terms' => ['pedas', 'pedes', 'geprek', 'mercon'],
        ],
        'minum' => [
            'categories' => ['Minuman'],
        ],
        'minuman' => [
            'categories' => ['Minuman'],
        ],
        'makan' => [
            'categories' => ['Makanan berat'],
        ],
        'makanan' => [
            'categories' => ['Makanan berat'],
        ],
        'nasi' => [
            'categories' => ['Makanan berat'],
            'name_terms' => ['nasi'],
        ],
        'jajan' => [
            'categories' => ['Camilan'],
        ],
        'cemilan' => [
            'categories' => ['Camilan'],
        ],
        'camilan' => [
            'categories' => ['Camilan'],
        ],
    ],
];

