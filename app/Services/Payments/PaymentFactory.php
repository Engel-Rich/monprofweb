<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentFactory
{
    /**
     * Les stratégies enregistrées : 'CAMPAY' => CampayPaymentStrategy::class
     */
    protected static array $strategies = [
        'CAMPAY' => CampayPaymentStrategy::class,
        // 'STRIPE' => StripePaymentStrategy::class,
        // 'PAYPAL' => PaypalPaymentStrategy::class,
    ];

    public static function make(string $provider): PaymentStrategy
    {
        $key   = strtoupper($provider);
        $class = static::$strategies[$key] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Payment provider [{$provider}] not supported.");
        }

        return app($class); // résolu via le conteneur Laravel
    }

    /**
     * Enregistre dynamiquement une nouvelle stratégie.
     */
    public static function extend(string $provider, string $strategyClass): void
    {
        static::$strategies[strtoupper($provider)] = $strategyClass;
    }
}