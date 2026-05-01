<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Response;
use App\Utils\Logger;
use App\Repositories\UserRepository;

final class AuthMiddleware
{
    public function handle(callable $next): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please log in to continue.'];
            Response::redirect('/login');
        }

        // Check session age (max 8 hours)
        $loginAt = $_SESSION['login_at'] ?? 0;
        if (time() - $loginAt > 8 * 3600) {
            session_destroy();
            session_start();
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Session expired. Please log in again.'];
            Response::redirect('/login');
        }

        // Validate user still exists and is active
        $userRepo = new UserRepository();
        $user = $userRepo->findById((int) $_SESSION['user_id']);
        
        if (!$user) {
            session_destroy();
            Response::redirect('/login');
        }

        // Attach user to global context for this request
        $GLOBALS['user'] = $user;

        $next();
    }
}
