<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db\Database;
use PDO;

final class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findByVerificationToken(string $token): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE verification_token = ?', [$token]);
    }

    public function create(array $data): int
    {
        return (int) $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->execute($sql, $params);
        return $this->db->rowCount($stmt) > 0;
    }
}
