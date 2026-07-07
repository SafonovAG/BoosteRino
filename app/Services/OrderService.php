<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class OrderService
{
    private PricingService $price;
    private TwiboostClient $tb;

    public function __construct()
    {
        $this->price = new PricingService();
        $this->tb = new TwiboostClient();
    }

    public function create(int $uid, int $sid, string $link, int $qty, string $pay): array
    {
        $svc = ServiceCatalog::find($sid);
        if (!$svc || !$svc['is_active']) {
            throw new \InvalidArgumentException('Услуга не найдена.');
        }
        if ($qty < $svc['min_qty'] || $qty > $svc['max_qty']) {
            throw new \InvalidArgumentException('Неверное количество.');
        }
        $link = (new LinkValidator())->validate($svc, $link);
        if (!in_array($pay, ['balance', 'yoomoney'], true)) {
            throw new \InvalidArgumentException('Неверный способ оплаты.');
        }
        $cost = $this->price->forService($svc, $qty);
        return $pay === 'balance'
            ? $this->viaBalance($uid, $svc, $link, $qty, $cost)
            : $this->viaYoomoney($uid, $svc, $link, $qty, $cost);
    }

    public function list(int $uid): array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, s.name service_name FROM orders o JOIN services s ON s.id=o.service_id WHERE o.user_id=:u ORDER BY o.id DESC LIMIT 100'
        );
        $st->execute(['u' => $uid]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['status_label'] = OrderStatus::label((string) $row['status']);
            $row['quantity_unit'] = DeliveryUnit::fromName((string) ($row['service_name'] ?? ''));
            $row = $this->sanitizeForClient($row);
        }
        return $rows;
    }

    public function getDetailForUser(int $uid, int $oid, bool $syncSupplier = true): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, s.name AS service_name, s.category AS service_category, s.type AS service_type,
                    s.refill AS service_refill, s.cancel AS service_cancel, s.id AS service_id
             FROM orders o
             JOIN services s ON s.id = o.service_id
             WHERE o.id = :id AND o.user_id = :u'
        );
        $st->execute(['id' => $oid, 'u' => $uid]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o) {
            return null;
        }

        if ($syncSupplier && !empty($o['twiboost_order_id'])) {
            $o = $this->applySupplierStatus($oid, (int) $o['twiboost_order_id'], $o);
        }

        return $this->enrichForUser($o);
    }

    /** @param list<int> $ids */
    public function listDetailsForUser(int $uid, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $out = [];
        foreach ($ids as $id) {
            $o = $this->getDetailForUser($uid, $id, true);
            if ($o) {
                $out[] = $o;
            }
        }
        return $out;
    }

    private function applySupplierStatus(int $orderId, int $tbId, array $local): array
    {
        try {
            $stat = $this->tb->status($tbId);
            Database::pdo()->prepare(
                'UPDATE orders SET status=:s, charge=:c, remains=:r, start_count=:sc, updated_at=NOW() WHERE id=:id'
            )->execute([
                's' => $stat['status'] ?? $local['status'],
                'c' => $stat['charge'] ?? null,
                'r' => $stat['remains'] ?? null,
                'sc' => $stat['start_count'] ?? null,
                'id' => $orderId,
            ]);
            $st = Database::pdo()->prepare(
                'SELECT o.*, s.name AS service_name, s.category AS service_category, s.type AS service_type,
                        s.refill AS service_refill, s.cancel AS service_cancel, s.id AS service_id
                 FROM orders o JOIN services s ON s.id = o.service_id WHERE o.id = :id'
            );
            $st->execute(['id' => $orderId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: $local;
            if (isset($stat['currency'])) {
                $row['supplier_currency'] = (string) $stat['currency'];
            }
            return $row;
        } catch (\Throwable) {
            return $local;
        }
    }

    private function enrichForUser(array $o): array
    {
        $o['service_refill'] = (bool) ($o['service_refill'] ?? false);
        $o['status_label'] = OrderStatus::label((string) ($o['status'] ?? ''));
        $o['status_active'] = OrderStatus::isActive((string) ($o['status'] ?? ''));
        $o['service_logo'] = ServiceLogo::forService([
            'name' => (string) ($o['service_name'] ?? ''),
            'category' => (string) ($o['service_category'] ?? ''),
            'type' => (string) ($o['service_type'] ?? ''),
        ]);


        $unit = DeliveryUnit::fromName((string) ($o['service_name'] ?? ''));
        $o['quantity_unit'] = $unit;
        $qty = (int) ($o['quantity'] ?? 0);
        $remains = ($o['remains'] !== null && $o['remains'] !== '') ? max(0, (int) $o['remains']) : null;
        $delivered = ($remains !== null && $qty > 0) ? max(0, $qty - $remains) : null;

        $o['delivery'] = [
            'unit' => $unit,
            'ordered' => $qty,
            'delivered' => $delivered,
            'remains' => $remains,
        ];

        $o['progress'] = $this->buildProgress($o);
        $o['synced_at'] = date('c');
        return $this->sanitizeForClient($o);
    }

    /** Убирает служебные поля поставщика из ответа для клиента. */
    private function sanitizeForClient(array $o): array
    {
        $internalId = (int) ($o['id'] ?? 0);
        $supplierId = !empty($o['twiboost_order_id']) ? (int) $o['twiboost_order_id'] : null;
        $o['order_number'] = $supplierId ?? $internalId;

        unset(
            $o['twiboost_order_id'],
            $o['charge'],
            $o['start_count'],
            $o['supplier_currency'],
            $o['supplier_charge_formatted'],
            $o['supplier_synced'],
            $o['uses_supplier_order_number'],
            $o['internal_order_id'],
            $o['display_order_id'],
            $o['service_cancel'],
        );
        return $o;
    }

  /** @return array{percent: float, done: int, total: int, remains: int}|null */
    private function buildProgress(array $o): ?array
    {
        $qty = (int) ($o['quantity'] ?? 0);
        if ($qty <= 0) {
            return null;
        }
        if ($o['remains'] !== null && $o['remains'] !== '') {
            $rem = max(0, (int) $o['remains']);
            $done = max(0, $qty - $rem);
            $pct = min(100, round(($done / $qty) * 100, 1));
            return ['percent' => $pct, 'done' => $done, 'total' => $qty, 'remains' => $rem];
        }
        $status = (string) ($o['status'] ?? '');
        if (in_array($status, ['Completed'], true) || str_contains(strtolower($status), 'complet')) {
            return ['percent' => 100, 'done' => $qty, 'total' => $qty, 'remains' => 0];
        }
        return null;
    }

    public function refill(int $uid, int $oid): array
    {
        $o = $this->own($uid, $oid);
        $s = ServiceCatalog::find((int) $o['service_id']);
        if (!$s || !$s['refill'] || !$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Рефилл недоступен.');
        }
        $this->tb->refill((int) $o['twiboost_order_id']);
        return ['ok' => true];
    }

    public function cancel(int $uid, int $oid): array
    {
        $o = $this->own($uid, $oid);
        $s = ServiceCatalog::find((int) $o['service_id']);
        if (!$s || !$s['cancel'] || !$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Отмена недоступна.');
        }
        $this->tb->cancel((int) $o['twiboost_order_id']);
        Database::pdo()->prepare('UPDATE orders SET status=\'Canceled\' WHERE id=:id')->execute(['id' => $oid]);
        return ['ok' => true];
    }

    public function sync(): int
    {
        $rows = Database::pdo()->query(
            "SELECT id,twiboost_order_id FROM orders WHERE twiboost_order_id IS NOT NULL AND status IN ('In progress','Awaiting','Partial','pending','pending_payment')"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            return 0;
        }
        $ids = array_column($rows, 'twiboost_order_id');
        $stat = $this->tb->statuses(array_map('intval', $ids));
        $up = Database::pdo()->prepare('UPDATE orders SET status=:s,charge=:c,remains=:r,start_count=:sc,updated_at=NOW() WHERE id=:id');
        $n = 0;
        foreach ($rows as $o) {
            $k = (string) $o['twiboost_order_id'];
            if (!isset($stat[$k]) || !is_array($stat[$k])) {
                continue;
            }
            $x = $stat[$k];
            $up->execute([
                's' => $x['status'] ?? $o['status'], 'c' => $x['charge'] ?? null,
                'r' => $x['remains'] ?? null, 'sc' => $x['start_count'] ?? null, 'id' => $o['id'],
            ]);
            $n++;
        }
        return $n;
    }

    public function fulfill(int $orderId): void
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM orders WHERE id=:id AND status=\'pending_payment\'');
        $st->execute(['id' => $orderId]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o) {
            return;
        }
        $svc = ServiceCatalog::find((int) $o['service_id']);
        if ($svc) {
            $this->sendTb($pdo, $o, $svc);
        }
    }

    private function viaBalance(int $uid, array $svc, string $link, int $qty, float $cost): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT balance_rub FROM users WHERE id=:id FOR UPDATE');
            $st->execute(['id' => $uid]);
            $bal = (float) $st->fetchColumn();
            if ($bal < $cost) {
                throw new \InvalidArgumentException('Недостаточно средств.');
            }
            $newBal = $bal - $cost;
            $pdo->prepare('UPDATE users SET balance_rub=:b WHERE id=:id')->execute(['b' => $newBal, 'id' => $uid]);
            $oid = $this->insert($pdo, $uid, $svc, $link, $qty, $cost, 'balance');
            $pdo->prepare('INSERT INTO balance_transactions (user_id,type,amount_rub,balance_after,reference_type,reference_id) VALUES (:u,\'order\',:a,:b,\'order\',:r)')
                ->execute(['u' => $uid, 'a' => -$cost, 'b' => $newBal, 'r' => $oid]);
            $o = $this->get($pdo, $oid);
            $this->sendTb($pdo, $o, $svc);
            $pdo->commit();
            return $this->sanitizeForClient($this->get($pdo, $oid));
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function viaYoomoney(int $uid, array $svc, string $link, int $qty, float $cost): array
    {
        $pdo = Database::pdo();
        $oid = $this->insert($pdo, $uid, $svc, $link, $qty, $cost, 'yoomoney', 'pending_payment');
        $pay = (new PaymentService())->forOrder($uid, $oid, $cost);
        $pdo->prepare('UPDATE orders SET payment_id=:p WHERE id=:id')->execute(['p' => $pay['id'], 'id' => $oid]);
        return ['order' => $this->sanitizeForClient($this->get($pdo, $oid)), 'payment_url' => $pay['payment_url']];
    }

    private function insert(PDO $pdo, int $uid, array $svc, string $link, int $qty, float $cost, string $pay, string $status = 'pending'): int
    {
        $pdo->prepare('INSERT INTO orders (user_id,service_id,link,quantity,cost_rub,payment_method,status) VALUES (:u,:s,:l,:q,:c,:p,:st)')
            ->execute(['u' => $uid, 's' => $svc['id'], 'l' => $link, 'q' => $qty, 'c' => $cost, 'p' => $pay, 'st' => $status]);
        return (int) $pdo->lastInsertId();
    }

    private function sendTb(PDO $pdo, array $o, array $svc): void
    {
        $r = $this->tb->add((int) $svc['external_id'], $o['link'], (int) $o['quantity']);
        if (!isset($r['order'])) {
            throw new \RuntimeException('Не удалось создать заказ. Попробуйте позже.');
        }
        $pdo->prepare('UPDATE orders SET twiboost_order_id=:t,status=\'Awaiting\' WHERE id=:id')
            ->execute(['t' => (int) $r['order'], 'id' => $o['id']]);
    }

    private function get(PDO $pdo, int $id): array
    {
        $st = $pdo->prepare('SELECT * FROM orders WHERE id=:id');
        $st->execute(['id' => $id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    private function own(int $uid, int $oid): array
    {
        $st = Database::pdo()->prepare('SELECT * FROM orders WHERE id=:id AND user_id=:u');
        $st->execute(['id' => $oid, 'u' => $uid]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
        return $o;
    }

    public function adminList(?string $status = null, ?string $search = null): array
    {
        $sql = 'SELECT o.*, u.email, u.id AS user_id, s.name AS service_name, s.external_id
                FROM orders o
                JOIN users u ON u.id = o.user_id
                JOIN services s ON s.id = o.service_id
                WHERE 1=1';
        $params = [];
        if ($status !== null && $status !== '' && $status !== 'all') {
            $sql .= ' AND o.status = :status';
            $params['status'] = $status;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND (o.id = :id OR u.email LIKE :q OR s.name LIKE :q OR o.link LIKE :q)';
            $params['q'] = '%' . $search . '%';
            $params['id'] = is_numeric($search) ? (int) $search : 0;
        }
        $sql .= ' ORDER BY o.id DESC LIMIT 300';
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adminGet(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT o.*, u.email, u.id AS user_id, s.name AS service_name, s.external_id, s.category, s.id AS service_id
             FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN services s ON s.id = o.service_id
             WHERE o.id = :id'
        );
        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function adminUpdateStatus(int $id, string $status): void
    {
        $allowed = [
            'pending', 'pending_payment', 'Awaiting', 'In progress', 'Partial',
            'Completed', 'Canceled', 'Cancelled', 'Fail', 'Failed', 'Error',
        ];
        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Недопустимый статус.');
        }
        $st = Database::pdo()->prepare('UPDATE orders SET status=:s, updated_at=NOW() WHERE id=:id');
        $st->execute(['s' => $status, 'id' => $id]);
        if ($st->rowCount() === 0) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
    }

    public function adminSyncOne(int $id): array
    {
        $o = $this->adminGet($id);
        if (!$o) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
        if (!$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Нет ID у поставщика.');
        }
        $stat = $this->tb->status((int) $o['twiboost_order_id']);
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE orders SET status=:s, charge=:c, remains=:r, start_count=:sc, updated_at=NOW() WHERE id=:id'
        )->execute([
            's' => $stat['status'] ?? $o['status'],
            'c' => $stat['charge'] ?? null,
            'r' => $stat['remains'] ?? null,
            'sc' => $stat['start_count'] ?? null,
            'id' => $id,
        ]);
        return $this->adminGet($id) ?? [];
    }

    public function adminRefill(int $id): array
    {
        $o = $this->adminGet($id);
        if (!$o || !$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Рефилл недоступен.');
        }
        return $this->tb->refill((int) $o['twiboost_order_id']);
    }

    public function adminCancel(int $id): array
    {
        $o = $this->adminGet($id);
        if (!$o) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
        $result = ['canceled_locally' => true];
        if ($o['twiboost_order_id']) {
            try {
                $result['supplier'] = $this->tb->cancel((int) $o['twiboost_order_id']);
            } catch (\Throwable $e) {
                $result['supplier_error'] = $e->getMessage();
            }
        }
        Database::pdo()->prepare('UPDATE orders SET status=\'Canceled\', updated_at=NOW() WHERE id=:id')->execute(['id' => $id]);
        return $result;
    }

    public function adminDelete(int $id): void
    {
        $o = $this->adminGet($id);
        if (!$o) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE payments SET order_id = NULL WHERE order_id = :id')->execute(['id' => $id]);
            $st = $pdo->prepare('DELETE FROM orders WHERE id = :id');
            $st->execute(['id' => $id]);
            if ($st->rowCount() === 0) {
                throw new \InvalidArgumentException('Заказ не найден.');
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
