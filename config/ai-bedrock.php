<?php

return [
    'driver' => 'bedrock',
    'access_key' => env('AWS_ACCESS_KEY_ID'),
    'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
    'session_token' => env('AWS_SESSION_TOKEN'),
    'region' => env('AWS_BEDROCK_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    'max_tokens' => env('AWS_BEDROCK_MAX_TOKENS', 16_384),
    'models' => [
        'text' => [
            'default' => env('AWS_BEDROCK_TEXT_MODEL', 'anthropic.claude-sonnet-4-5-20250929-v1:0'),
            'cheapest' => env('AWS_BEDROCK_CHEAPEST_MODEL', 'anthropic.claude-haiku-4-5-20251001-v1:0'),
            'smartest' => env('AWS_BEDROCK_SMARTEST_MODEL', 'anthropic.claude-opus-4-6-v1:0'),
        ],
        'embeddings' => [
            'default' => env('AWS_BEDROCK_EMBEDDINGS_MODEL', 'amazon.titan-embed-text-v2:0'),
            'dimensions' => env('AWS_BEDROCK_EMBEDDINGS_DIMENSIONS', 1024),
        ],
    ],
];
