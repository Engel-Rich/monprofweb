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
];
