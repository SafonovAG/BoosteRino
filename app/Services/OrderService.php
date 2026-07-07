<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class OrderService
{
    private PricingService $pricing;
    private TwiboostClient $twiboost;

    public function __construct()
    {
        $this->pricing = new PricingService();
        $this->twiboost = new TwiboostClient();
    }

    public function create(int $userId, int $serviceId, string $link, int $quantity, string $paymentMethod): array
    {
        $service = ServiceCatalog::find($serviceId);
        if (!$service || !(int) $service['is_active']) {
            throw new \InvalidArgumentException('Услуга не найдена.');
        }
        if ($quantity < (int) $service['min_qty'] || $quantity > (int) $service['max_qty']) {
            throw new \InvalidArgumentException('Количество вне допустимого диапазона.');
        }
        if (!in_array($paymentMethod, ['balance', 'yoomoney'], true)) {
            throw new \InvalidArgumentException('Некорректный способ оплаты.');
        }

        $cost = $this->pricing->forService($service, $quantity);
        $pdo = Database::connection();

        if ($paymentMethod === 'balance') {
            return $this->createWithBalance($pdo, $userId, $service, $link, $quantity, $cost);
        }

        return $this->createWithYooMoney($pdo, $userId, $service, $link, $quantity, $cost);
    }

    public function listForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT o.*, s.name AS service_name FROM orders o
             JOIN services s ON s.id = o.service_id
             WHERE o.user_id = :user_id ORDER BY o.id DESC LIMIT 100'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function refill(int $userId, int $orderId): array
    {
        $order = $this->findUserOrder($userId, $orderId);
        $service = ServiceCatalog::find((int) $order['service_id']);
        if (!$service || (int) $service['refill'] !== 1) {
            throw new \InvalidArgumentException('Рефилл недоступен для этого заказа.');
        }
        if (!$order['twiboost_order_id']) {
            throw new \InvalidArgumentException('Заказ ещё не создан у поставщика.');
        }

        return $this->twiboost->refill((int) $order['twiboost_order_id']);
    }

    public function cancel(int $userId, int $orderId): array
    {
        $order = $this->findUserOrder($userId, $orderId);
        $service = ServiceCatalog::find((int) $order['service_id']);
        if (!$service || (int) $service['cancel'] !== 1) {
            throw new \InvalidArgumentException('Отмена недоступна для этого заказа.');
        }
        if (!$order['twiboost_order_id']) {
            throw new \InvalidArgumentException('Заказ ещё не создан у поставщика.');
        }

        $result = $this->twiboost->cancel((int) $order['twiboost_order_id']);
        Database::connection()->prepare('UPDATE orders SET status = :status WHERE id = :id')
            ->execute(['status' => 'Canceled', 'id' => $orderId]);

        return $result;
    }

    public function syncActiveStatuses(): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT id, twiboost_order_id FROM orders
             WHERE twiboost_order_id IS NOT NULL
             AND status IN ('In progress', 'Awaiting', 'Partial', 'pending', 'pending_payment')"
        );
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($orders === []) {
            return 0;
        }

        $ids = array_column($orders, 'twiboost_order_id');
        $statuses = $this->twiboost->ordersStatus(array_map('intval', $ids));
        $updated = 0;

        $update = $pdo->prepare(
            'UPDATE orders SET status = :status, charge = :charge, remains = :remains,
             start_count = :start_count, updated_at = NOW() WHERE id = :id'
        );

        foreach ($orders as $order) {
            $tbId = (string) $order['twiboost_order_id'];
            if (!isset($statuses[$tbId]) || !is_array($statuses[$tbId])) {
                continue;
            }
            $s = $statuses[$tbId];
            $update->execute([
                'status' => (string) ($s['status'] ?? $order['status']),
                'charge' => isset($s['charge']) ? (float) $s['charge'] : null,
                'remains' => isset($s['remains']) ? (int) $s['remains'] : null,
                'start_count' => isset($s['start_count']) ? (int) $s['start_count'] : null,
                'id' => $order['id'],
            ]);
            $updated++;
        }

        return $updated;
    }

    public function fulfillPendingOrder(int $orderId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND status = :status LIMIT 1');
        $stmt->execute(['id' => $orderId, 'status' => 'pending_payment']);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return;
        }

        $service = ServiceCatalog::find((int) $order['service_id']);
        if (!$service) {
            return;
        }

        $this->placeTwiboostOrder($pdo, $order, $service);
    }

    private function createWithBalance(PDO $pdo, int $userId, array $service, string $link, int $quantity, float $cost): array
    {
        $pdo->beginTransaction();
        try {
            $userStmt = $pdo->prepare('SELECT balance_rub FROM users WHERE id = :id FOR UPDATE');
            $userStmt->execute(['id' => $userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ((float) $user['balance_rub'] < $cost) {
                throw new \InvalidArgumentException('Недостаточно средств на балансе.');
            }

            $newBalance = (float) $user['balance_rub'] - $cost;
            $pdo->prepare('UPDATE users SET balance_rub = :balance WHERE id = :id')
                ->execute(['balance' => $newBalance, 'id' => $userId]);

            $orderId = $this->insertOrder($pdo, $userId, $service, $link, $quantity, $cost, 'balance');
            $this->recordTransaction($pdo, $userId, 'order_debit', -$cost, $newBalance, 'order', $orderId);

            $order = $this->getOrder($pdo, $orderId);
            $this->placeTwiboostOrder($pdo, $order, $service);

            $pdo->commit();
            return $this->getOrder($pdo, $orderId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function createWithYooMoney(PDO $pdo, int $userId, array $service, string $link, int $quantity, float $cost): array
    {
        $orderId = $this->insertOrder($pdo, $userId, $service, $link, $quantity, $cost, 'yoomoney', 'pending_payment');
        $payment = (new PaymentService())->createOrderPayment($userId, $orderId, $cost);

        $pdo->prepare('UPDATE orders SET payment_id = :payment_id WHERE id = :id')
            ->execute(['payment_id' => $payment['id'], 'id' => $orderId]);

        return [
            'order' => $this->getOrder($pdo, $orderId),
            'payment_url' => $payment['payment_url'],
        ];
    }

    private function insertOrder(
        PDO $pdo,
        int $userId,
        array $service,
        string $link,
        int $quantity,
        float $cost,
        string $paymentMethod,
        string $status = 'pending',
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO orders (user_id, service_id, link, quantity, cost_rub, payment_method, status)
             VALUES (:user_id, :service_id, :link, :quantity, :cost, :payment_method, :status)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'service_id' => $service['id'],
            'link' => $link,
            'quantity' => $quantity,
            'cost' => $cost,
            'payment_method' => $paymentMethod,
            'status' => $status,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function placeTwiboostOrder(PDO $pdo, array $order, array $service): void
    {
        $response = $this->twiboost->addOrder(
            (int) $service['external_id'],
            (string) $order['link'],
            (int) $order['quantity']
        );

        if (!isset($response['order'])) {
            throw new \RuntimeException('Не удалось создать заказ у поставщика.');
        }

        $pdo->prepare(
            'UPDATE orders SET twiboost_order_id = :tb_id, status = :status, updated_at = NOW() WHERE id = :id'
        )->execute([
            'tb_id' => (int) $response['order'],
            'status' => 'Awaiting',
            'id' => $order['id'],
        ]);
    }

    private function recordTransaction(
        PDO $pdo,
        int $userId,
        string $type,
        float $amount,
        float $balanceAfter,
        string $refType,
        int $refId,
    ): void {
        $pdo->prepare(
            'INSERT INTO balance_transactions (user_id, type, amount_rub, balance_after, reference_type, reference_id)
             VALUES (:user_id, :type, :amount, :balance_after, :ref_type, :ref_id)'
        )->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    private function getOrder(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function findUserOrder(int $userId, int $orderId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new \InvalidArgumentException('Заказ не найден.');
        }
        return $order;
    }
}
