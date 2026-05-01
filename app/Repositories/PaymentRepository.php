<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db\Database;

final class PaymentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public function findByTranref(string $tranref): ?array
    {
        return $this->db->fetchOne('SELECT * FROM payments WHERE tranref = ?', [$tranref]);
    }

    public function findByIdempotencyKey(string $key): ?array
    {
        return $this->db->fetchOne('SELECT * FROM payments WHERE idempotency_key = ?', [$key]);
    }

    public function create(array $data): int
    {
        return (int) $this->db->insert('payments', $data);
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        $params[] = $id;

        $sql = 'UPDATE payments SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->execute($sql, $params);
        return $this->db->rowCount($stmt) > 0;
    }
    
    public function markCompleted(int $id, string $status, array $response = []): bool
    {
        return $this->update($id, [
            'status' => $status,
            'provider_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
