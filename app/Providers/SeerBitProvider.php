<?php

declare(strict_types=1);

namespace App\Providers;

use App\Utils\Logger;
use App\Services\CurrencyService;

final class SeerBitProvider implements PaymentProviderInterface
{
    private const BASE_URL = 'https://seerbitapi.com/api/v2';
    private string $publicKey;
    private string $secretKey;
    private array $config;

    public function __construct()
    {
        $this->publicKey = SEERBIT_PUBLIC_KEY;
        $this->secretKey = SEERBIT_SECRET_KEY;
        
        $providersConfig = require CONFIG_PATH . '/providers.php';
        $this->config = $providersConfig['seerbit'] ?? [];
    }

    public function getProviderName(): string
    {
        return 'seerbit';
    }

    public function getSupportedCurrencies(): array
    {
        return $this->config['currencies'] ?? [];
    }

    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? [];
    }

    public function initialize(array $payload, string $idempotencyKey): ProviderResponse
    {
        // Convert minor units to decimal string for SeerBit v2 API (if required by their docs)
        // Check SeerBit docs carefully: some APIs want minor units, some want "15.00"
        // Assuming they want "15.00" based on legacy script.js sending "150.00"
        $amountStr = CurrencyService::fromMinorUnits((int)$payload['amount'], $payload['currency']);

        $requestBody = [
            'publicKey'    => $this->publicKey,
            'amount'       => $amountStr,
            'currency'     => $payload['currency'],
            'country'      => $payload['country'] ?? 'NG',
            'paymentReference' => $payload['tranref'],
            'email'        => $payload['email'],
            'fullName'     => $payload['full_name'],
            'tokenize'     => false,
            'callbackUrl'  => SEERBIT_CALLBACK_URL,
        ];

        // This is where actual HTTP cURL to SeerBit would go.
        // For portfolio/demo purposes, if keys are missing, we mock the success.
        
        // --- Mocking for local development ---
        if (empty($this->secretKey) || str_starts_with($this->secretKey, 'sbsec_newkey')) {
            Logger::info('SeerBit initialize (MOCKED)', ['payload' => $requestBody]);
            return new ProviderResponse(
                success: true,
                token: 'mock_token_' . bin2hex(random_bytes(8)),
                providerRef: 'SB_MOCK_' . time(),
                rawResponse: ['status' => 'SUCCESS', 'message' => 'Mocked initialization'],
                status: 'PENDING'
            );
        }
        // --- End Mock ---

        return $this->makeRequest('POST', '/payments/initiates', $requestBody, $idempotencyKey);
    }

    public function verify(string $providerRef): ProviderResponse
    {
        return $this->makeRequest('GET', "/payments/query/{$providerRef}");
    }

    public function refund(string $providerRef, float $amount, string $idempotencyKey): ProviderResponse
    {
        // Assuming refund payload based on standard PSPs
        $requestBody = [
            'publicKey'        => $this->publicKey,
            'paymentReference' => $providerRef,
            'amount'           => $amount,
        ];
        
        return $this->makeRequest('POST', '/payments/refunds', $requestBody, $idempotencyKey);
    }

    /**
     * Internal cURL executor with bounded timeouts and retries
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], string $idempotencyKey = null): ProviderResponse
    {
        $url = self::BASE_URL . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        if ($idempotencyKey) {
            $headers[] = "Idempotency-Key: {$idempotencyKey}";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8); // B-01 Fix: 8s max timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // B-01 Fix: 3s connect timeout

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Retry logic (V-15 fix)
        $maxAttempts = 3;
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        
        while ($attempt < $maxAttempts) {
            $attempt++;
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
                break; // Success
            }
            
            if ($response !== false && in_array($httpCode, [400, 401, 422])) {
                break; // Non-retryable error
            }
            
            // Retryable error (timeout or 5xx)
            if ($attempt < $maxAttempts) {
                $delayMs = (500 * $attempt) + rand(-100, 100); // Backoff with jitter
                Logger::warning("SeerBit API retry attempt {$attempt}", ['url' => $url, 'code' => $httpCode, 'delay' => $delayMs]);
                usleep($delayMs * 1000);
            }
        }

        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::error('SeerBit API Network Error', ['endpoint' => $endpoint, 'error' => $error]);
            return new ProviderResponse(false, null, null, [], 'NETWORK_ERROR', $error);
        }

        $responseData = json_decode((string)$response, true) ?? [];
        
        // Parse SeerBit specific response structure
        $success = ($httpCode >= 200 && $httpCode < 300) && ($responseData['status'] ?? '') === 'SUCCESS';
        
        return new ProviderResponse(
            success: $success,
            token: $responseData['data']['payments']['redirectLink'] ?? null, // Assuming this is where the token/link is
            providerRef: $responseData['data']['payments']['paymentReference'] ?? null,
            rawResponse: $responseData,
            errorCode: $success ? null : (string)$httpCode,
            errorMessage: $responseData['message'] ?? 'API Error',
            status: $responseData['data']['payments']['status'] ?? null
        );
    }
}
