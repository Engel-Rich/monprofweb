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
        'duration' => (int) env('PAYMENT_POLLING_DURATION', 55),
        'interval' => (int) env('PAYMENT_POLLING_INTERVAL', 5),
        'chunk' => (int) env('PAYMENT_POLLING_CHUNK', 100),
    ],
];
