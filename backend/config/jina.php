<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jina AI Semantic Search Configuration
    |--------------------------------------------------------------------------
    |
    | All settings for Jina AI embeddings and duplicate detection.
    | Values are hardcoded here – no .env references.
    |
    */

    'provider' => 'jina',

    'api_key' => 'jina_xxxxx', // Replace with your actual key

    'embedding_model' => 'jina-embeddings-v5-text-small',

    'api_url' => 'https://api.jina.ai/v1/embeddings',

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