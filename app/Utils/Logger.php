<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Logger — Structured JSON-line logger (PSR-3 inspired).
 *
 * Writes to storage/logs/app.log. Each line is a valid JSON object,
 * directly ingestible by Datadog, CloudWatch, or ELK with no parser config.
 *
 * NEVER log: passwords, API secret keys, card numbers, session tokens.
 */
final class Logger
{
    private const LOG_FILE = STORAGE_PATH . '/logs/app.log';

    private const LEVELS = [
        'DEBUG'    => 0,
        'INFO'     => 1,
        'WARNING'  => 2,
        'ERROR'    => 3,
        'CRITICAL' => 4,
    ];

    private static string $minLevel = 'DEBUG';

    public static function setMinLevel(string $level): void
    {
        self::$minLevel = strtoupper($level);
    }

    // ─── Level methods ────────────────────────────────────────────────────────

    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    // ─── Core write ───────────────────────────────────────────────────────────

    private static function write(string $level, string $message, array $context): void
    {
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[self::$minLevel] ?? 0)) {
            return;
        }

        // Scrub sensitive keys from context before logging
        $context = self::scrub($context);

        $entry = json_encode([
            'timestamp' => date('c'),
            'level'     => $level,
            'message'   => $message,
            'context'   => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Ensure log directory exists
        $dir = dirname(self::LOG_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Append with file lock to prevent race conditions across workers
        file_put_contents(self::LOG_FILE, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Remove sensitive keys from context arrays recursively.
     */
    private static function scrub(array $data): array
    {
        $redacted = ['password', 'password_hash', 'secret', 'token', 'api_key',
                     'secret_key', 'private_key', 'card_number', 'cvv', 'ssn'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $redacted, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = self::scrub($value);
            }
        }
        return $data;
    }
}
