<?php

return [
    'provider' => env('SEMANTIC_PROVIDER', 'jina'),

    'jina' => [
        'api_key' => env('JINA_API_KEY'),
        'embedding_model' => env('JINA_EMBEDDING_MODEL', 'jina-embeddings-v5-text-small'),
        'api_url' => env('JINA_API_URL', 'https://api.jina.ai/v1/embeddings'),
        'timeout' => env('SEMANTIC_TIMEOUT', 10),
    ],

    'thresholds' => [
        'same_language' => (int) env('SAME_LANGUAGE_THRESHOLD', 90),
        'cross_language'  => (int) env('CROSS_LANGUAGE_THRESHOLD', 85),
        'translation'     => (int) env('TRANSLATION_THRESHOLD', 75),   // new
    ],

    'max_jina_candidates' => (int) env('MAX_JINA_CANDIDATES', 20),
];