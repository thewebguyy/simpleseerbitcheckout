<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Csrf — CSRF token generation and validation.
 *
 * Tokens are stored in the server-side session.
 * A hidden field must be present in every POST form.
 * Every POST handler validates before any business logic runs.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Generate (or retrieve) the current session CSRF token.
     * Call once per GET request that renders a form.
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = generateToken(32);
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Render a hidden HTML input field for forms.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(self::token()) . '">';
    }

    /**
     * Validate the submitted token against the session token.
     * Uses hash_equals() — constant-time comparison prevents timing attacks.
     *
     * Returns true if valid. On failure: logs and returns false.
     * The caller is responsible for aborting the request.
     */
    public static function validate(): bool
    {
        $submitted = $_POST['csrf_token'] ?? '';
        $expected  = $_SESSION[self::SESSION_KEY] ?? '';

        if (empty($submitted) || empty($expected)) {
            Logger::critical('CSRF token missing', [
                'ip'  => self::ip(),
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            return false;
        }

        if (!hash_equals($expected, $submitted)) {
            Logger::critical('CSRF token mismatch', [
                'ip'  => self::ip(),
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            self::regenerate(); // Burn the old token on failure
            return false;
        }

        return true;
    }

    /**
     * Regenerate the CSRF token. Call after successful form processing
     * to prevent token reuse (double-submit).
     */
    public static function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = generateToken(32);
    }

    private static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
