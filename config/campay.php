<?php

return [

    // Sandbox
    'url' => env('CAMPAY_URL'),
    'username' => env('CAMPAY_USERNAME'),
    'password' => env('CAMPAY_PASSWORD'),

    // Production
    'prod_url' => env('CAMPAY_PROD_URL'),
    'prod_username' => env('CAMPAY_PROD_USERNAME'),
    'prod_password' => env('CAMPAY_PROD_PASSWORD'),

    // Clé de webhook (« WEBHOOK KEY » du tableau de bord CamPay). Utilisée pour
    // vérifier la signature JWT envoyée avec chaque notification. Tant qu'elle
    // n'est pas renseignée, l'endpoint accepte des notifications non signées.
    'webhook_key' => env('CAMPAY_WEBHOOK_KEY'),

    // Durée de mise en cache du token d'authentification (minutes).
    // Les tokens CamPay sont valides ~15 minutes.
    'token_ttl' => (int) env('CAMPAY_TOKEN_TTL', 10),

];
