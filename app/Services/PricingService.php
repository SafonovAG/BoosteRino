<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PricingService
{
    public function price(float $rate, int $qty, ?float $override = null): float
    {
        $markup = $override ?? (new SettingsService())->getFloat('global_markup_percent', 30);
        $base = ($rate / 1000) * $qty;
        return ceil($base * (1 + $markup / 100) * 100) / 100;
    }

    public function forService(array $s, int $qty): float
    {
        $ov = $s['markup_override'] !== null ? (float) $s['markup_override'] : null;
        return $this->price((float) $s['rate'], $qty, $ov);
    }

    public function format(array $s): array
    {
        $ov = $s['markup_override'] !== null ? (float) $s['markup_override'] : null;
        return [
            'id' => (int) $s['id'],
            'external_id' => (int) $s['external_id'],
            'name' => $s['name'],
            'type' => $s['type'],
            'category' => $s['category'],
            'price_per_thousand_rub' => $this->price((float) $s['rate'], 1000, $ov),
            'min' => (int) $s['min_qty'],
            'max' => (int) $s['max_qty'],
            'refill' => (bool) $s['refill'],
            'cancel' => (bool) $s['cancel'],
        ];
    }
}

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
