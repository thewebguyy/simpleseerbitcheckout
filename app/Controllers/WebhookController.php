<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Logger;
use App\Services\WebhookService;

final class WebhookController
{
    public function receive(): void
    {
        // 1. Raw body capture (CRITICAL for HMAC)
        $rawBody = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_SEERBIT_SIGNATURE'] ?? $_SERVER['HTTP_HTTP_X_SEERBIT_SIGNATURE'] ?? '';

        $webhookService = new WebhookService();

        // 2. Validate Signature
        $isValid = $webhookService->validateSignature($rawBody, $signature);
        if (!$isValid) {
            Logger::critical('Webhook signature mismatch', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
            // Return 401, don't process
            Response::json(['message' => 'Unauthorized'], 401);
        }

        // 3. Parse Payload
        $payload = json_decode($rawBody, true);
        if (!$payload) {
            Logger::error('Webhook invalid JSON payload', ['raw' => $rawBody]);
            Response::json(['message' => 'Bad Request'], 400);
        }

        $eventType = $payload['eventType'] ?? $payload['type'] ?? 'UNKNOWN';
        
        // Structure varies by provider. Assuming standard SeerBit v2 structure:
        // payload -> data -> payments -> paymentReference
        // In the legacy script it sent 'tranref'.
        $tranref = $payload['data']['payments']['paymentReference'] 
                ?? $payload['data']['paymentReference'] 
                ?? $payload['tranref'] 
                ?? null;
                
        $status = $payload['data']['payments']['status'] 
               ?? $payload['data']['status'] 
               ?? $payload['status'] 
               ?? 'UNKNOWN';

        if (!$tranref) {
            Logger::error('Webhook missing tranref', ['payload' => $payload]);
            Response::json(['message' => 'Unprocessable Entity'], 422);
        }

        // 4. Log Event & Idempotency Check
        $eventId = $webhookService->logEvent($eventType, $tranref, $payload, $signature, $isValid);
        
        if ($eventId === false) {
            // Duplicate event, acknowledge to stop retries
            Response::json(['message' => 'Acknowledged'], 200);
        }

        // 5. Async Boundary (Phase 5)
        // For standard hosting, if Queue is not fully set up with a worker yet, we do it sync.
        // In a true async setup, we would insert into `jobs` table here and return 200.
        // For completeness of Phase 5, we should dispatch to Queue here.
        // Since we are mocking the QueueService currently, we'll process synchronously for the demo, 
        // but note the boundary.
        
        try {
            $webhookService->processEvent((int)$eventId, $tranref, $status, $payload);
        } catch (\Exception $e) {
            // Processing failed, but we still return 200 to SeerBit so they don't retry.
            // The job is recorded and can be replayed.
            Logger::error('Webhook synchronous processing failed', ['error' => $e->getMessage()]);
        }

        // 6. Return 200 Fast
        Response::json(['message' => 'Acknowledged'], 200);
    }
}
