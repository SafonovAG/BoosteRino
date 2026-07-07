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
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function refill(int $uid, int $oid): array
    {
        $o = $this->own($uid, $oid);
        $s = ServiceCatalog::find((int) $o['service_id']);
        if (!$s || !$s['refill'] || !$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Рефилл недоступен.');
        }
        return $this->tb->refill((int) $o['twiboost_order_id']);
    }

    public function cancel(int $uid, int $oid): array
    {
        $o = $this->own($uid, $oid);
        $s = ServiceCatalog::find((int) $o['service_id']);
        if (!$s || !$s['cancel'] || !$o['twiboost_order_id']) {
            throw new \InvalidArgumentException('Отмена недоступна.');
        }
        $r = $this->tb->cancel((int) $o['twiboost_order_id']);
        Database::pdo()->prepare('UPDATE orders SET status=\'Canceled\' WHERE id=:id')->execute(['id' => $oid]);
        return $r;
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
            return $this->get($pdo, $oid);
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
        return ['order' => $this->get($pdo, $oid), 'payment_url' => $pay['payment_url']];
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
            throw new \RuntimeException('Ошибка создания заказа у поставщика.');
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
}
