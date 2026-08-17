<?php

namespace App\Services\Payments;

use App\Models\PaymentProvider;
use InvalidArgumentException;
use RuntimeException;

class PaymentFactory
{
    protected static array $extensions = [];

    public static function make(PaymentProvider|string|null $provider = null): PaymentStrategy
    {
        $provider = static::resolveProvider($provider);
        $key = strtoupper($provider->code);
        $class = static::$extensions[$key] ?? config("payments.strategies.{$key}");

        if (! $class) {
            throw new InvalidArgumentException("Payment provider [{$provider->code}] not supported.");
        }

        $strategy = app($class);

        if (! $strategy instanceof PaymentStrategy) {
            throw new InvalidArgumentException("Payment strategy [{$class}] is invalid.");
        }

        return $strategy;
    }

    /**
     * Enregistre dynamiquement une nouvelle stratégie.
     */
    public static function extend(string $provider, string $strategyClass): void
    {
        static::$extensions[strtoupper($provider)] = $strategyClass;
    }

    public static function supports(string $provider): bool
    {
        $key = strtoupper($provider);

        return isset(static::$extensions[$key]) || filled(config("payments.strategies.{$key}"));
    }

    private static function resolveProvider(PaymentProvider|string|null $provider): PaymentProvider
    {
        if ($provider instanceof PaymentProvider) {
            return $provider;
        }

        if (is_string($provider)) {
            return PaymentProvider::query()
                ->where('code', strtoupper($provider))
                ->firstOrFail();
        }

        return PaymentProvider::active()->first()
            ?? throw new RuntimeException('Aucun fournisseur de paiement actif n’est configuré.');
    }
}
