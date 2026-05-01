<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;
use App\Utils\Csrf;

final class CsrfMiddleware
{
    public function handle(callable $next): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!Csrf::validate()) {
                Response::error('Invalid CSRF token', 403);
            }
        }
        
        $next();
    }
}
