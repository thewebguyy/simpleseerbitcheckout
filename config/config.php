<?php

declare(strict_types=1);

/**
 * config.php — Application bootstrap.
 *
 * Loads .env, defines constants, hardens session configuration,
 * and exposes shared helpers. Must be the first include in every
 * entry point (public/index.php, worker.php).
 */

// ─── 1. Root path ────────────────────────────────────────────────────────────

define('ROOT_PATH',    dirname(__DIR__));
define('APP_PATH',     ROOT_PATH . '/app');
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH',    APP_PATH  . '/Views');

// ─── 2. Load .env ─────────────────────────────────────────────────────────────

$envFile = ROOT_PATH . '/.env';

if (!file_exists($envFile)) {
    http_response_code(500);
    error_log('[CRITICAL] .env file not found at: ' . $envFile);
    exit('Server configuration error.');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    if (!str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key   = trim($key);
    $value = trim($value, " \t\n\r\0\x0B\"'");
    if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
        putenv("$key=$value");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

// ─── 3. Environment helper ───────────────────────────────────────────────────

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return match (strtolower($value)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $value,
    };
}

// ─── 4. Required variable guard ──────────────────────────────────────────────

$required = [
    'APP_ENV',
    'SEERBIT_PUBLIC_KEY',
    'SEERBIT_SECRET_KEY',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
];

foreach ($required as $var) {
    if (env($var) === null) {
        http_response_code(500);
        error_log("[CRITICAL] Required environment variable missing: {$var}");
        exit('Server configuration error.');
    }
}

// ─── 5. Application constants ────────────────────────────────────────────────

define('APP_ENV',              env('APP_ENV',  'production'));
define('APP_NAME',             env('APP_NAME', 'SeerBit Checkout'));
define('APP_URL',              rtrim((string) env('APP_URL', 'http://localhost'), '/'));
define('APP_DEBUG',            APP_ENV === 'development');

define('SEERBIT_PUBLIC_KEY',   env('SEERBIT_PUBLIC_KEY'));
define('SEERBIT_SECRET_KEY',   env('SEERBIT_SECRET_KEY'));
define('SEERBIT_CALLBACK_URL', env('SEERBIT_CALLBACK_URL', APP_URL . '/webhook'));

define('DB_HOST',    env('DB_HOST'));
define('DB_PORT',    (int) env('DB_PORT', 3306));
define('DB_NAME',    env('DB_NAME'));
define('DB_USER',    env('DB_USER'));
define('DB_PASS',    env('DB_PASS'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('SESSION_NAME',     env('SESSION_NAME',     'seerbit_session'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 3600));

define('WEBHOOK_ROUTE_SECRET', env('WEBHOOK_ROUTE_SECRET', ''));

// ─── 6. Error reporting ──────────────────────────────────────────────────────

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php_errors.log');

// ─── 7. Session hardening ─────────────────────────────────────────────────────

ini_set('session.use_strict_mode',   '1');
ini_set('session.use_only_cookies',  '1');
ini_set('session.cookie_httponly',   '1');
ini_set('session.cookie_samesite',   'Lax');
ini_set('session.gc_maxlifetime',    (string) SESSION_LIFETIME);
ini_set('session.name',              SESSION_NAME);

if (!APP_DEBUG && str_starts_with(APP_URL, 'https')) {
    ini_set('session.cookie_secure', '1');
}

// ─── 8. Autoloader (lightweight PSR-4 style) ─────────────────────────────────

spl_autoload_register(function (string $class): void {
    // Map namespace prefix to directory
    $prefixes = [
        'App\\'    => APP_PATH . '/',
        'Config\\' => CONFIG_PATH . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// ─── 9. Global helper functions ──────────────────────────────────────────────

/**
 * XSS-safe HTML output. Use for every dynamic value echoed into HTML context.
 */
function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate a cryptographically secure random token.
 */
function generateToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Redirect and exit. Never call header() + exit() directly.
 */
function redirect(string $url, int $code = 302): never
{
    http_response_code($code);
    header('Location: ' . $url);
    exit;
}

/**
 * Abort with an HTTP status and message.
 */
function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    if ($message) {
        echo h($message);
    }
    exit;
}
