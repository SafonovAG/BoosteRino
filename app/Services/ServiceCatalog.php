<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ServiceCatalog
{
    private static bool $descriptionReady = false;

    public static function ensureDescriptionColumn(): void
    {
        if (self::$descriptionReady) {
            return;
        }
        $pdo = Database::pdo();
        try {
            $pdo->query('SELECT description FROM services LIMIT 1');
        } catch (\PDOException) {
            $pdo->exec('ALTER TABLE services ADD COLUMN description TEXT NULL DEFAULT NULL AFTER category');
        }
        self::$descriptionReady = true;
    }

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

    /** @return list<array{category: string, cnt: int, active_cnt: int}> */
    public static function adminCategories(): array
    {
        return Database::pdo()->query(
            'SELECT category, COUNT(*) AS cnt, SUM(is_active) AS active_cnt
             FROM services GROUP BY category ORDER BY category'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function adminRenameCategory(string $oldName, string $newName): int
    {
        $oldName = trim($oldName);
        $newName = trim($newName);
        if ($oldName === '' || $newName === '') {
            throw new \InvalidArgumentException('Укажите название категории.');
        }
        if ($oldName === $newName) {
            return 0;
        }
        $st = Database::pdo()->prepare('UPDATE services SET category=:n WHERE category=:o');
        $st->execute(['n' => $newName, 'o' => $oldName]);
        return $st->rowCount();
    }

    public static function adminUpdate(int $id, array $data): ?array
    {
        $svc = self::find($id);
        if (!$svc) {
            return null;
        }

        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                throw new \InvalidArgumentException('Название не может быть пустым.');
            }
            $fields[] = 'name=:name';
            $params['name'] = $name;
        }
        if (array_key_exists('type', $data)) {
            $fields[] = 'type=:type';
            $params['type'] = trim((string) $data['type']);
        }
        if (array_key_exists('category', $data)) {
            $cat = trim((string) $data['category']);
            if ($cat === '') {
                throw new \InvalidArgumentException('Категория не может быть пустой.');
            }
            $fields[] = 'category=:category';
            $params['category'] = $cat;
        }
        if (array_key_exists('description', $data)) {
            $fields[] = 'description=:description';
            $params['description'] = trim((string) $data['description']) ?: null;
        }
        if (array_key_exists('rate', $data)) {
            $fields[] = 'rate=:rate';
            $params['rate'] = (float) $data['rate'];
        }
        if (array_key_exists('min_qty', $data)) {
            $fields[] = 'min_qty=:min_qty';
            $params['min_qty'] = max(0, (int) $data['min_qty']);
        }
        if (array_key_exists('max_qty', $data)) {
            $fields[] = 'max_qty=:max_qty';
            $params['max_qty'] = max(0, (int) $data['max_qty']);
        }
        if (array_key_exists('refill', $data)) {
            $fields[] = 'refill=:refill';
            $params['refill'] = filter_var($data['refill'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (array_key_exists('cancel', $data)) {
            $fields[] = 'cancel=:cancel';
            $params['cancel'] = filter_var($data['cancel'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (array_key_exists('is_active', $data)) {
            $fields[] = 'is_active=:is_active';
            $params['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (array_key_exists('markup_override', $data)) {
            $m = $data['markup_override'];
            $fields[] = 'markup_override=:markup_override';
            $params['markup_override'] = ($m === '' || $m === null) ? null : (float) $m;
        }

        if (!$fields) {
            return $svc;
        }

        $sql = 'UPDATE services SET ' . implode(', ', $fields) . ' WHERE id=:id';
        Database::pdo()->prepare($sql)->execute($params);
        return self::find($id);
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
             ON DUPLICATE KEY UPDATE rate=VALUES(rate), min_qty=VALUES(min_qty), max_qty=VALUES(max_qty),
             refill=VALUES(refill), cancel=VALUES(cancel), synced_at=NOW()'
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
