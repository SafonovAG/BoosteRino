<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ServiceCatalog
{
    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT * FROM services WHERE id=:id');
        $st->execute(['id' => $id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function active(): array
    {
        return Database::pdo()->query('SELECT * FROM services WHERE is_active=1 ORDER BY category,name')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function sync(): int
    {
        $list = (new TwiboostClient())->services();
        if (!is_array($list)) {
            return 0;
        }
        $pdo = Database::pdo();
        $up = $pdo->prepare(
            'INSERT INTO services (external_id,name,type,category,rate,min_qty,max_qty,refill,cancel,synced_at)
             VALUES (:e,:n,:t,:c,:r,:mi,:ma,:rf,:cn,NOW())
             ON DUPLICATE KEY UPDATE name=VALUES(name),type=VALUES(type),category=VALUES(category),
             rate=VALUES(rate),min_qty=VALUES(min_qty),max_qty=VALUES(max_qty),refill=VALUES(refill),cancel=VALUES(cancel),synced_at=NOW()'
        );
        $n = 0;
        foreach ($list as $item) {
            if (!isset($item['service'])) {
                continue;
            }
            $up->execute([
                'e' => (int) $item['service'], 'n' => (string) ($item['name'] ?? ''),
                't' => (string) ($item['type'] ?? ''), 'c' => (string) ($item['category'] ?? ''),
                'r' => (float) ($item['rate'] ?? 0), 'mi' => (int) ($item['min'] ?? 0),
                'ma' => (int) ($item['max'] ?? 0), 'rf' => !empty($item['refill']) ? 1 : 0,
                'cn' => !empty($item['cancel']) ? 1 : 0,
            ]);
            $n++;
        }
        return $n;
    }
}
