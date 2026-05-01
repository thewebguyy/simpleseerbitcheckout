<?php

declare(strict_types=1);

namespace App\Middleware;

final class SessionMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $next();
    }
}
