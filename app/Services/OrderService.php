<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Utils\Logger;
use Exception;

final class OrderService
{
    private OrderRepository $orderRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
    }

    /**
     * Create an order in DRAFT status.
     * Returns the generated order_reference.
     */
    public function create(array $orderData, array $items = []): string
    {
        $reference = $this->generateReference();

        $orderData['order_reference'] = $reference;
        $orderData['status'] = 'PENDING_PAYMENT';
        $orderData['expires_at'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Minor units are expected in $orderData (total_amount, subtotal)
        
        $orderId = $this->orderRepo->create($orderData, $items);
        Logger::info('Order created', ['order_id' => $orderId, 'reference' => $reference]);

        return $reference;
    }

    public function transitionState(int $orderId, string $newStatus): bool
    {
        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            throw new Exception("Order not found: {$orderId}");
        }

        $currentStatus = $order['status'];

        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            Logger::critical('Invalid order state transition', [
                'order_id' => $orderId,
                'from' => $currentStatus,
                'to' => $newStatus
            ]);
            throw new Exception("Invalid transition from {$currentStatus} to {$newStatus}");
        }

        $success = $this->orderRepo->updateStatus($orderId, $newStatus);
        
        if ($success) {
            Logger::info('Order state changed', [
                'order_id' => $orderId,
                'from' => $currentStatus,
                'to' => $newStatus
            ]);
        }
        
        return $success;
    }

    private function isValidTransition(string $from, string $to): bool
    {
        $transitions = [
            'DRAFT' => ['PENDING_PAYMENT', 'EXPIRED'],
            'PENDING_PAYMENT' => ['PROCESSING', 'CANCELLED', 'EXPIRED', 'FAILED', 'PAID'], // Added PAID as immediate success is possible
            'PROCESSING' => ['PAID', 'FAILED'],
            'PAID' => ['REFUNDED'],
            // Terminal states
            'CANCELLED' => [],
            'EXPIRED' => [],
            'FAILED' => [],
            'REFUNDED' => [],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }

    private function generateReference(): string
    {
        $date = date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
        return "ORD-{$date}-{$random}";
    }
}
