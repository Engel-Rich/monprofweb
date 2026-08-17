<?php

return [
    'driver' => strtolower(env('FILE_STORAGE_DRIVER', 'firebase')),

    'encryption_key' => env('ENCRYPTION_KEY'),

    'firebase' => [
        'url_ttl' => (int) env('FIREBASE_FILE_URL_TTL', 30),
    ],

    'minio' => [
        // URL interne utilisée par PHP pour communiquer avec MinIO.
        'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9000'),
        'key' => env('MINIO_ACCESS_KEY', env('MINIO_ROOT_USER')),
        'secret' => env('MINIO_SECRET_KEY', env('MINIO_ROOT_PASSWORD')),
        'region' => env('MINIO_REGION', 'us-east-1'),
        'bucket' => env('MINIO_BUCKET', 'monprof'),
        // URL publique HTTPS lisible depuis l'application mobile ou le navigateur.
        'public_url' => env('MINIO_PUBLIC_URL'),
        'use_path_style_endpoint' => filter_var(
            env('MINIO_USE_PATH_STYLE_ENDPOINT', true),
            FILTER_VALIDATE_BOOL,
        ),
    ],
];
