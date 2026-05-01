<?php

declare(strict_types=1);

namespace App\Providers;

final class ProviderResponse
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $token = null,
        private readonly ?string $providerRef = null,
        private readonly array $rawResponse = [],
        private readonly ?string $errorCode = null,
        private readonly ?string $errorMessage = null,
        private readonly ?string $status = null
    ) {}

    public function success(): bool
    {
        return $this->success;
    }

    public function token(): ?string
    {
        return $this->token;
    }

    public function providerRef(): ?string
    {
        return $this->providerRef;
    }

    public function rawResponse(): array
    {
        return $this->rawResponse;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function status(): ?string
    {
        return $this->status;
    }
}
