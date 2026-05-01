<?php

declare(strict_types=1);

namespace App\Providers;

use RuntimeException;

final class ProviderRegistry
{
    private static array $providers = [];

    public static function register(string $name, PaymentProviderInterface $provider): void
    {
        self::$providers[strtolower($name)] = $provider;
    }

    public static function get(string $name): PaymentProviderInterface
    {
        $name = strtolower($name);
        if (!isset(self::$providers[$name])) {
            throw new RuntimeException("Payment provider not registered: {$name}");
        }
        return self::$providers[$name];
    }

    public static function all(): array
    {
        return self::$providers;
    }

    public static function supports(string $currency, string $country): array
    {
        $eligible = [];
        $currency = strtoupper($currency);
        $country  = strtoupper($country);

        foreach (self::$providers as $name => $provider) {
            /** @var PaymentProviderInterface $provider */
            if (
                in_array($currency, $provider->getSupportedCurrencies(), true) &&
                in_array($country, $provider->getSupportedCountries(), true)
            ) {
                $eligible[$name] = $provider;
            }
        }

        return $eligible;
    }
}
