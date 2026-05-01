<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\CurrencyService;

final class CheckoutController
{
    public function showForm(): void
    {
        $currencies = require CONFIG_PATH . '/currencies.php';
        
        Response::view('checkout/form', [
            'currencies' => array_keys($currencies),
        ]);
    }

    public function initialize(): void
    {
        // 1. Input Validation (Phase 1/5)
        $result = Validator::make($_POST, [
            'amount'           => 'required|numeric|positive|decimal:2',
            'currency'         => 'required',
            'email'            => 'required|email|max:254',
            'full_name'        => 'required|max:100',
            'billing_address'  => 'max:500',
            'shipping_address' => 'max:500',
            'payment_method'   => 'required',
        ]);

        if ($result->fails()) {
            Response::error('Validation failed', 422, $result->errors());
        }

        if (!CurrencyService::isSupported($_POST['currency'])) {
            Response::error('Unsupported currency', 422, ['currency' => ['Currency is not supported.']]);
        }

        // 2. Map input to internal representation (minor units)
        $amountMinorUnits = CurrencyService::toMinorUnits((float) $_POST['amount'], $_POST['currency']);

        $orderData = [
            'user_id'          => $_SESSION['user_id'] ?? null, // Null if guest
            'currency'         => $_POST['currency'],
            'subtotal'         => $amountMinorUnits,
            'total_amount'     => $amountMinorUnits,
            'billing_name'     => $_POST['full_name'],
            'billing_email'    => $_POST['email'],
            'billing_address'  => $_POST['billing_address'] ?? null,
            'shipping_address' => $_POST['shipping_address'] ?? null,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent'       => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        try {
            // 3. Create Order
            $orderService = new OrderService();
            $reference = $orderService->create($orderData); // Creates DRAFT, transitions to PENDING_PAYMENT

            $orderRepo = new \App\Repositories\OrderRepository();
            $order = $orderRepo->findByReference($reference);

            // 4. Initialize Payment
            $paymentService = new PaymentService();
            $paymentResult = $paymentService->initialize($order, [
                'email'     => $_POST['email'],
                'full_name' => $_POST['full_name'],
                'country'   => 'NG' // Could be derived from IP or billing address
            ]);

            if ($paymentResult['success']) {
                // Return JSON payload for frontend SDK
                Response::success([
                    'token'   => $paymentResult['token'],
                    'tranref' => $paymentResult['tranref'],
                    'order_reference' => $reference
                ], 'Payment initialized');
            } else {
                Response::error($paymentResult['error'], 502); // 502 Bad Gateway
            }

        } catch (\Throwable $e) {
            Logger::critical('Checkout initialization failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Response::error('An unexpected error occurred. Please try again.', 500);
        }
    }

    public function confirmation(): void
    {
        $reference = $_GET['ref'] ?? null;
        if (!$reference) {
            Response::redirect('/');
        }

        $orderRepo = new \App\Repositories\OrderRepository();
        $order = $orderRepo->findByReference($reference);

        if (!$order) {
            Response::notFound('Order not found.');
        }

        // If auth is required, verify ownership here
        // if ($order['user_id'] !== $_SESSION['user_id']) { Response::forbidden(); }

        Response::view('checkout/confirmation', [
            'order' => $order
        ]);
    }
}
