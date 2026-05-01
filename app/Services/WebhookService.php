<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventRepository;
use App\Utils\Logger;
use Exception;

final class WebhookService
{
    private EventRepository $eventRepo;
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->eventRepo = new EventRepository();
        $this->paymentService = new PaymentService();
    }

    public function validateSignature(string $rawBody, string $signature): bool
    {
        $expected = hash_hmac('sha256', $rawBody, SEERBIT_SECRET_KEY);
        return hash_equals($expected, $signature);
    }

    /**
     * Write raw event and check idempotency. Returns event ID if new, false if duplicate.
     */
    public function logEvent(string $eventType, string $tranref, array $payload, string $signature, bool $isValid): int|false
    {
        if ($this->eventRepo->exists($tranref, $eventType)) {
            Logger::info('Duplicate webhook received and ignored', ['tranref' => $tranref, 'type' => $eventType]);
            return false;
        }

        return $this->eventRepo->logEvent([
            'event_type'      => $eventType,
            'tranref'         => $tranref,
            'payload'         => $payload,
            'signature'       => $signature,
            'signature_valid' => $isValid ? 1 : 0,
            'processed'       => 0
        ]);
    }

    public function processEvent(int $eventId, string $tranref, string $status, array $rawPayload): void
    {
        try {
            $this->paymentService->processWebhook($tranref, $status, $rawPayload);
            $this->eventRepo->markProcessed($eventId);
        } catch (Exception $e) {
            Logger::error('Webhook processing failed', [
                'event_id' => $eventId,
                'tranref'  => $tranref,
                'error'    => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
