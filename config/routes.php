<?php

declare(strict_types=1);

use App\Router;
use App\Controllers\CheckoutController;
use App\Controllers\WebhookController;

$router = new Router();

// API Endpoints
$router->post('/api/checkout/initialize', [CheckoutController::class, 'initialize'], ['csrf']);
$router->post('/webhook', [WebhookController::class, 'receive']); // No CSRF or Auth, SeerBit calls this

// Web Pages
$router->get('/', [CheckoutController::class, 'showForm']);
$router->get('/checkout/confirmation', [CheckoutController::class, 'confirmation']);

return $router;
