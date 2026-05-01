<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Db\Database;

final class OrderRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM orders WHERE id = ?', [$id]);
    }

    public function findByReference(string $reference): ?array
    {
        return $this->db->fetchOne('SELECT * FROM orders WHERE order_reference = ?', [$reference]);
    }

    public function findWithItems(string $reference): ?array
    {
        $order = $this->findByReference($reference);
        if (!$order) {
            return null;
        }

        $order['items'] = $this->db->fetchAll(
            'SELECT * FROM order_items WHERE order_id = ?',
            [$order['id']]
        );

        return $order;
    }

    public function create(array $orderData, array $items = []): int
    {
        return $this->db->transaction(function (Database $db) use ($orderData, $items) {
            $orderId = (int) $db->insert('orders', $orderData);

            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                $db->insert('order_items', $item);
            }

            return $orderId;
        });
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = 'UPDATE orders SET status = ? WHERE id = ?';
        $stmt = $this->db->execute($sql, [$status, $id]);
        return $this->db->rowCount($stmt) > 0;
    }
}
