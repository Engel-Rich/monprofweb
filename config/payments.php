<?php

use App\Services\MundiPayService;
use App\Services\Payments\CampayPaymentStrategy;

return [
    // La base choisit le fournisseur actif. Cette liste constitue l'allowlist
    // des implémentations techniques que l'application peut instancier.
    'strategies' => [
        'CAMPAY' => CampayPaymentStrategy::class,
        'MUNDIPAY' => MundiPayService::class,
    ],

    'polling' => [
        'scheduler_enabled' => filter_var(env('PAYMENT_SCHEDULER_ENABLED', true), FILTER_VALIDATE_BOOL),
        'duration' => (int) env('PAYMENT_POLLING_DURATION', 55),
        'interval' => (int) env('PAYMENT_POLLING_INTERVAL', 5),
        'chunk' => (int) env('PAYMENT_POLLING_CHUNK', 100),

        // Au-delà de cet âge (en minutes) une transaction n'est plus interrogée
        // auprès du fournisseur : elle est expirée. Sans cette borne, chaque
        // transaction restée PENDING est repolled toutes les 5 secondes à vie.
        'max_age' => (int) env('PAYMENT_POLLING_MAX_AGE', 180),

        // Expire automatiquement en FAILED les transactions dépassant max_age.
        // Désactivable si l'on préfère un traitement manuel.
        'expire_stale' => filter_var(env('PAYMENT_POLLING_EXPIRE_STALE', true), FILTER_VALIDATE_BOOL),
    ],

    'mundi' => [
        'url' => env('MUNDY_PAY_API_URL', 'https://gateway.mundipay.pro/api/smobilpay/'),
        'key' => env('MUNDY_PAY_API_KEY'),
        'secret' => env('MUNDY_PAY_API_SECRET'),
    ],
];
