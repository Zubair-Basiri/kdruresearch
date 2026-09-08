<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jina AI Semantic Search Configuration
    |--------------------------------------------------------------------------
    |
    | All settings for Jina AI embeddings and duplicate detection.
    |
    */

    'provider' => 'jina',

    // Keep credentials outside source control. When no key is configured the
    // duplicate checker safely skips the optional semantic comparison.
    'api_key' => env('JINA_API_KEY'),

    'embedding_model' => env('JINA_EMBEDDING_MODEL', 'jina-embeddings-v5-text-small'),

    'api_url' => env('JINA_API_URL', 'https://api.jina.ai/v1/embeddings'),

    'timeout' => 10,

    'same_language_threshold' => 90,

    'cross_language_threshold' => 75,

    'translation_threshold' => 75,

    'max_candidates' => 20,

    'cache_ttl' => [
        'embedding' => 86400,   // 24 hours
        'translation' => 3600,  // 1 hour
    ],

    'translation' => [
        'my_memory' => [
            'enabled' => true,
            'url' => 'https://api.mymemory.translated.net/get',
            'timeout' => 6,
        ],
        'libre_translate' => [
            'enabled' => true,
            'url' => 'https://libretranslate.com/translate',
            'timeout' => 5,
        ],
    ],
];
