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
    ],

    'mundi' => [
        'url' => env('MUNDY_PAY_API_URL', 'https://gateway.mundipay.pro/api/smobilpay/'),
        'key' => env('MUNDY_PAY_API_KEY'),
        'secret' => env('MUNDY_PAY_API_SECRET'),
    ],
];
