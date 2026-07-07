<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ServiceCatalog
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function activeList(): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM services WHERE is_active = 1 ORDER BY category, name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function syncFromTwiboost(): int
    {
        $client = new TwiboostClient();
        $services = $client->services();
        $pdo = Database::connection();
        $count = 0;

        if (!is_array($services)) {
            return 0;
        }

        $upsert = $pdo->prepare(
            'INSERT INTO services (external_id, name, type, category, rate, min_qty, max_qty, refill, cancel, synced_at)
             VALUES (:external_id, :name, :type, :category, :rate, :min_qty, :max_qty, :refill, :cancel, NOW())
             ON DUPLICATE KEY UPDATE
               name = VALUES(name), type = VALUES(type), category = VALUES(category),
               rate = VALUES(rate), min_qty = VALUES(min_qty), max_qty = VALUES(max_qty),
               refill = VALUES(refill), cancel = VALUES(cancel), synced_at = NOW()'
        );

        foreach ($services as $service) {
            if (!isset($service['service'])) {
                continue;
            }
            $upsert->execute([
                'external_id' => (int) $service['service'],
                'name' => (string) ($service['name'] ?? ''),
                'type' => (string) ($service['type'] ?? ''),
                'category' => (string) ($service['category'] ?? ''),
                'rate' => (float) ($service['rate'] ?? 0),
                'min_qty' => (int) ($service['min'] ?? 0),
                'max_qty' => (int) ($service['max'] ?? 0),
                'refill' => !empty($service['refill']) ? 1 : 0,
                'cancel' => !empty($service['cancel']) ? 1 : 0,
            ]);
            $count++;
        }

        return $count;
    }
}
