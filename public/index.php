<?php

declare(strict_types=1);

/**
 * Public Entry Point
 * All HTTP requests must be routed through this file via .htaccess.
 */

// 1. Bootstrap the application
require dirname(__DIR__) . '/config/config.php';

// 2. Load routes
$router = require CONFIG_PATH . '/routes.php';

// 3. Dispatch the request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

$router->dispatch($method, $uri);
