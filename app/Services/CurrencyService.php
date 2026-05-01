<?php

declare(strict_types=1);

namespace App\Services;

use NumberFormatter;
use RuntimeException;

final class CurrencyService
{
    private static ?array $currencies = null;

    private static function getCurrencies(): array
    {
        if (self::$currencies === null) {
            self::$currencies = require CONFIG_PATH . '/currencies.php';
        }
        return self::$currencies;
    }

    public static function isSupported(string $code): bool
    {
        return isset(self::getCurrencies()[strtoupper($code)]);
    }

    public static function getConfig(string $code): array
    {
        $code = strtoupper($code);
        if (!self::isSupported($code)) {
            throw new RuntimeException("Unsupported currency: {$code}");
        }
        return self::getCurrencies()[$code];
    }

    public static function getDecimalPlaces(string $code): int
    {
        return self::getConfig($code)['decimals'];
    }

    /**
     * Convert decimal amount (e.g. 15.50) to minor units (e.g. 1550).
     */
    public static function toMinorUnits(float $amount, string $code): int
    {
        $config = self::getConfig($code);
        return (int) round($amount * $config['factor']);
    }

    /**
     * Convert minor units (e.g. 1550) to decimal string (e.g. "15.50").
     */
    public static function fromMinorUnits(int $minorUnits, string $code): string
    {
        $config = self::getConfig($code);
        $decimalAmount = $minorUnits / $config['factor'];
        return number_format($decimalAmount, $config['decimals'], '.', '');
    }

    /**
     * Format minor units for display according to locale.
     */
    public static function format(int $minorUnits, string $code, string $locale = 'en-NG'): string
    {
        $code = strtoupper($code);
        $config = self::getConfig($code);
        $decimalAmount = $minorUnits / $config['factor'];

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($decimalAmount, $code);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        // Fallback if intl extension is missing
        return $config['symbol'] . number_format($decimalAmount, $config['decimals']);
    }
}
