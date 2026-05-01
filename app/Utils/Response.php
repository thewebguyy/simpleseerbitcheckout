<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Response — Standardized HTTP response helper.
 *
 * Eliminates scattered header() + exit() patterns.
 * Every exit path in the application goes through this class.
 */
final class Response
{
    // ─── JSON responses (API endpoints) ──────────────────────────────────────

    public static function json(mixed $data, int $status = 200): never
    {
        self::setJsonHeaders($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    // ─── HTML responses (page routes) ────────────────────────────────────────

    /**
     * Render a view template wrapped in the main layout.
     *
     * @param string $template  Relative path under app/Views/ e.g. 'checkout/form'
     * @param array  $data      Variables to extract into template scope
     * @param int    $status    HTTP status code
     */
    public static function view(string $template, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        self::setSecurityHeaders();

        // Make data available to the view
        extract($data, EXTR_SKIP);

        $templateFile = VIEW_PATH . '/' . $template . '.php';
        if (!file_exists($templateFile)) {
            http_response_code(500);
            echo 'View not found: ' . h($template);
            exit;
        }

        // Capture view content
        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        // Wrap in layout
        $layoutFile = VIEW_PATH . '/layouts/main.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }

        exit;
    }

    // ─── Redirects ────────────────────────────────────────────────────────────

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? APP_URL;
        self::redirect($referer);
    }

    // ─── Error pages ─────────────────────────────────────────────────────────

    public static function notFound(string $message = 'Page not found.'): never
    {
        self::view('errors/404', ['message' => $message], 404);
    }

    public static function forbidden(string $message = 'Access denied.'): never
    {
        self::view('errors/403', ['message' => $message], 403);
    }

    public static function serverError(string $message = 'An unexpected error occurred.'): never
    {
        self::view('errors/500', ['message' => $message], 500);
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    private static function setJsonHeaders(int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        self::setSecurityHeaders();
    }

    private static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header(
            "Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' https://cdn.seerbitapi.com; " .
            "style-src 'self' https://fonts.googleapis.com; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "img-src 'self' data:; " .
            "connect-src 'self' https://checkout.seerbitapi.com; " .
            "frame-src https://checkout.seerbitapi.com;"
        );
        if (!APP_DEBUG) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
