<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db\Database;

final class EventRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function exists(string $tranref, string $eventType): bool
    {
        $sql = 'SELECT 1 FROM payment_events WHERE tranref = ? AND event_type = ? LIMIT 1';
        return $this->db->fetchOne($sql, [$tranref, $eventType]) !== null;
    }

    public function logEvent(array $data): int
    {
        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload'], JSON_UNESCAPED_UNICODE);
        }
        return (int) $this->db->insert('payment_events', $data);
    }

    public function markProcessed(int $eventId): bool
    {
        $sql = 'UPDATE payment_events SET processed = 1 WHERE id = ?';
        $stmt = $this->db->execute($sql, [$eventId]);
        return $this->db->rowCount($stmt) > 0;
    }
}
