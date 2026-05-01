<?php

declare(strict_types=1);

namespace App\Providers;

interface PaymentProviderInterface
{
    /**
     * Initiate a payment.
     * @param array $payload Validated payment details (amount, currency, email, etc.)
     * @param string $idempotencyKey Unique key to prevent duplicate charges
     */
    public function initialize(array $payload, string $idempotencyKey): ProviderResponse;

    /**
     * Verify a transaction status server-to-server.
     */
    public function verify(string $providerRef): ProviderResponse;

    /**
     * Refund a completed transaction.
     */
    public function refund(string $providerRef, float $amount, string $idempotencyKey): ProviderResponse;

    public function getSupportedCurrencies(): array;

    public function getSupportedCountries(): array;

    public function getProviderName(): string;
}
