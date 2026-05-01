<?php

declare(strict_types=1);

namespace App\Db;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database — PDO singleton with prepared statement helpers.
 *
 * Usage:
 *   $db  = Database::getInstance();
 *   $row = $db->fetchOne('SELECT * FROM users WHERE id = ?', [1]);
 *   $db->execute('UPDATE users SET email = ? WHERE id = ?', [$email, $id]);
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
        } catch (PDOException $e) {
            // Never expose DB credentials in error messages
            error_log('[CRITICAL] Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database unavailable.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ─── Query helpers ────────────────────────────────────────────────────────

    /**
     * Prepare and execute a statement. Returns the PDOStatement.
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row. Returns null if not found.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->execute($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Fetch all matching rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single scalar value.
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $result = $this->execute($sql, $params)->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * Insert a row and return the new auto-increment ID.
     */
    public function insert(string $table, array $data): int|string
    {
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->execute(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return $this->pdo->lastInsertId();
    }

    // ─── Transaction helpers ──────────────────────────────────────────────────

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Execute a callable within a transaction.
     * Auto-commits on success, rolls back on exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    public function rowCount(PDOStatement $stmt): int
    {
        return $stmt->rowCount();
    }

    /** Prevent cloning of the singleton */
    private function __clone() {}
}
