<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Providers\ProviderRegistry;
use App\Utils\Logger;
use Exception;

final class PaymentService
{
    private PaymentRepository $paymentRepo;
    private ProviderSelectionService $selectionService;

    public function __construct()
    {
        $this->paymentRepo = new PaymentRepository();
        $this->selectionService = new ProviderSelectionService();
    }

    /**
     * @return array ['success' => bool, 'token' => ?string, 'error' => ?string, 'tranref' => ?string]
     */
    public function initialize(array $order, array $customerPayload): array
    {
        $country = $customerPayload['country'] ?? 'NG';
        $currency = $order['currency'];

        $provider = $this->selectionService->select($country, $currency);
        if (!$provider) {
            return ['success' => false, 'error' => 'No payment provider available for this region/currency.'];
        }

        $tranref = 'SB-' . strtoupper(bin2hex(random_bytes(8)));
        $idempotencyKey = 'IDEM-' . $tranref . '-' . $order['id'];

        $paymentData = [
            'order_id' => $order['id'],
            'provider' => $provider->getProviderName(),
            'tranref'  => $tranref,
            'idempotency_key' => $idempotencyKey,
            'amount'   => $order['total_amount'], // Integer minor units
            'currency' => $currency,
            'status'   => 'PENDING'
        ];

        $paymentId = $this->paymentRepo->create($paymentData);
        Logger::info('Payment record created', ['payment_id' => $paymentId, 'tranref' => $tranref]);

        $apiPayload = [
            'amount'    => $order['total_amount'],
            'currency'  => $currency,
            'tranref'   => $tranref,
            'email'     => $customerPayload['email'],
            'full_name' => $customerPayload['full_name'],
            'country'   => $country
        ];

        $response = $provider->initialize($apiPayload, $idempotencyKey);

        if ($response->success()) {
            $this->paymentRepo->update($paymentId, [
                'provider_transaction_ref' => $response->providerRef()
            ]);
            return [
                'success' => true,
                'token'   => $response->token(),
                'tranref' => $tranref
            ];
        }

        // Handle failure
        $this->paymentRepo->markCompleted($paymentId, 'FAILED', $response->rawResponse());
        Logger::error('Payment initialization failed', [
            'payment_id' => $paymentId,
            'error'      => $response->errorMessage()
        ]);

        return [
            'success' => false,
            'error'   => $response->errorMessage() ?? 'Payment service error',
            'tranref' => $tranref
        ];
    }

    public function processWebhook(string $tranref, string $status, array $rawResponse): void
    {
        $payment = $this->paymentRepo->findByTranref($tranref);
        if (!$payment) {
            throw new Exception("Payment not found for tranref: {$tranref}");
        }

        $newStatus = match(strtoupper($status)) {
            'SUCCESS', 'SUCCESSFUL' => 'SUCCESSFUL',
            'FAILED' => 'FAILED',
            'PENDING' => 'PROCESSING',
            'ABANDONED' => 'ABANDONED',
            'REVERSED', 'REFUNDED' => 'REFUNDED',
            default => 'PROCESSING'
        };

        $this->paymentRepo->markCompleted($payment['id'], $newStatus, $rawResponse);
        Logger::info("Payment status updated via webhook", [
            'payment_id' => $payment['id'],
            'new_status' => $newStatus
        ]);

        // Sync order status
        $orderService = new OrderService();
        $orderStatus = match($newStatus) {
            'SUCCESSFUL' => 'PAID',
            'FAILED' => 'FAILED',
            'PROCESSING' => 'PROCESSING',
            'ABANDONED' => 'CANCELLED',
            'REFUNDED' => 'REFUNDED',
            default => null
        };

        if ($orderStatus !== null) {
            try {
                $orderService->transitionState((int)$payment['order_id'], $orderStatus);
            } catch (Exception $e) {
                Logger::error('Failed to sync order state from payment', ['error' => $e->getMessage()]);
            }
        }
    }
}
