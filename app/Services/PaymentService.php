<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PaymentService
{
    private SettingsService $settings;

    public function __construct()
    {
        $this->settings = new SettingsService();
    }

    public function createTopup(int $userId, float $amount): array
    {
        if ($amount < 10) {
            throw new \InvalidArgumentException('Минимальная сумма пополнения — 10 ₽.');
        }

        return $this->createPayment($userId, 'topup', $amount);
    }

    public function createOrderPayment(int $userId, int $orderId, float $amount): array
    {
        $payment = $this->createPayment($userId, 'order', $amount, $orderId);
        $payment['id'] = $payment['payment_id'];
        return $payment;
    }

    public function handleNotification(array $data): bool
    {
        if (!$this->verifyNotification($data)) {
            return false;
        }

        $label = (string) ($data['label'] ?? '');
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE label = :label LIMIT 1');
        $stmt->execute(['label' => $label]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment || $payment['status'] === 'success') {
            return true;
        }

        $amount = (float) ($data['withdraw_amount'] ?? $data['amount'] ?? 0);
        if (abs((float) $payment['amount_rub'] - $amount) > 0.01) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE payments SET status = :status, yoomoney_operation_id = :op_id, updated_at = NOW() WHERE id = :id'
            )->execute([
                'status' => 'success',
                'op_id' => (string) ($data['operation_id'] ?? ''),
                'id' => $payment['id'],
            ]);

            if ($payment['type'] === 'topup') {
                $this->creditBalance($pdo, (int) $payment['user_id'], (float) $payment['amount_rub'], 'topup', (int) $payment['id']);
            } elseif ($payment['type'] === 'order' && $payment['order_id']) {
                (new OrderService())->fulfillPendingOrder((int) $payment['order_id']);
            }

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Payment notify error: ' . $e->getMessage());
            return false;
        }
    }

    public function listForUser(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM payments WHERE user_id = :user_id ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createPayment(int $userId, string $type, float $amount, ?int $orderId = null): array
    {
        $label = $this->generateLabel();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO payments (user_id, type, amount_rub, label, order_id, status)
             VALUES (:user_id, :type, :amount, :label, :order_id, :status)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'label' => $label,
            'order_id' => $orderId,
            'status' => 'pending',
        ]);

        return [
            'payment_id' => (int) $pdo->lastInsertId(),
            'label' => $label,
            'payment_url' => $this->buildQuickpayUrl($amount, $label),
        ];
    }

    private function buildQuickpayUrl(float $amount, string $label): string
    {
        $wallet = $this->settings->get('yoomoney_wallet');
        if ($wallet === '') {
            throw new \RuntimeException('Кошелёк ЮMoney не настроен.');
        }

        $params = http_build_query([
            'receiver' => $wallet,
            'quickpay-form' => 'shop',
            'targets' => 'Пополнение Boosterino',
            'paymentType' => 'PC',
            'sum' => number_format($amount, 2, '.', ''),
            'label' => $label,
            'successURL' => rtrim($this->settings->get('app_url'), '/') . '/cabinet?payment=success',
        ]);

        return 'https://yoomoney.ru/quickpay/confirm.xml?' . $params;
    }

    private function verifyNotification(array $data): bool
    {
        $secret = $this->settings->get('yoomoney_secret');
        if ($secret === '') {
            return false;
        }

        $string = implode('&', [
            $data['notification_type'] ?? '',
            $data['operation_id'] ?? '',
            $data['amount'] ?? '',
            $data['currency'] ?? '',
            $data['datetime'] ?? '',
            $data['sender'] ?? '',
            $data['codepro'] ?? '',
            $secret,
            $data['label'] ?? '',
        ]);

        $hash = sha1($string);

        return isset($data['sha1_hash']) && hash_equals($hash, (string) $data['sha1_hash']);
    }

    private function creditBalance(PDO $pdo, int $userId, float $amount, string $refType, int $refId): void
    {
        $stmt = $pdo->prepare('SELECT balance_rub FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $newBalance = (float) $user['balance_rub'] + $amount;

        $pdo->prepare('UPDATE users SET balance_rub = :balance WHERE id = :id')
            ->execute(['balance' => $newBalance, 'id' => $userId]);

        $pdo->prepare(
            'INSERT INTO balance_transactions (user_id, type, amount_rub, balance_after, reference_type, reference_id)
             VALUES (:user_id, :type, :amount, :balance_after, :ref_type, :ref_id)'
        )->execute([
            'user_id' => $userId,
            'type' => 'topup',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);
    }

    private function generateLabel(): string
    {
        return sprintf('%s-%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }
}
