<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class PaymentService
{
    public function topup(int $uid, float $amount): array
    {
        if ($amount < 10) {
            throw new \InvalidArgumentException('Минимум 10 руб.');
        }
        return $this->create($uid, 'topup', $amount);
    }

    public function forOrder(int $uid, int $orderId, float $amount): array
    {
        $p = $this->create($uid, 'order', $amount, $orderId);
        $p['id'] = $p['payment_id'];
        return $p;
    }

    public function notifyUrl(): string
    {
        $base = rtrim((new SettingsService())->get('app_url', 'https://boosterino.ru'), '/');
        return $base . '/api/v1/payments/yoomoney/notify';
    }

    public function notify(array $data): bool
    {
        if (!$this->verify($data)) {
            return false;
        }
        $label = (string) ($data['label'] ?? '');
        if ($label === '') {
            return false;
        }
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM payments WHERE label=:l');
        $st->execute(['l' => $label]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p || $p['status'] === 'success') {
            return true;
        }
        $amount = (float) ($data['withdraw_amount'] ?? $data['amount'] ?? 0);
        if (abs((float) $p['amount_rub'] - $amount) > 0.01) {
            return false;
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE payments SET status=\'success\', yoomoney_operation_id=:op WHERE id=:id')
                ->execute(['op' => (string) ($data['operation_id'] ?? ''), 'id' => $p['id']]);
            if ($p['type'] === 'topup') {
                $this->credit($pdo, (int) $p['user_id'], (float) $p['amount_rub'], 'topup', (int) $p['id']);
            } elseif ($p['order_id']) {
                (new OrderService())->fulfill((int) $p['order_id']);
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    private function create(int $uid, string $type, float $amount, ?int $orderId = null): array
    {
        $label = date('YmdHis') . '-' . bin2hex(random_bytes(8));
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO payments (user_id,type,amount_rub,label,order_id) VALUES (:u,:t,:a,:l,:o)')
            ->execute(['u' => $uid, 't' => $type, 'a' => $amount, 'l' => $label, 'o' => $orderId]);
        return [
            'payment_id' => (int) $pdo->lastInsertId(),
            'payment_url' => $this->quickpayUrl($amount, $label, $orderId),
        ];
    }

    private function quickpayUrl(float $amount, string $label, ?int $orderId = null): string
    {
        $s = new SettingsService();
        $wallet = $s->get('yoomoney_wallet');
        if ($wallet === '') {
            throw new \RuntimeException('Кошелёк ЮMoney не настроен.');
        }
        $base = rtrim($s->get('app_url'), '/');
        $successUrl = $orderId
            ? $base . '/orders/success?ids=' . $orderId
            : $base . '/cabinet?payment=ok';
        $q = http_build_query([
            'receiver' => $wallet,
            'quickpay-form' => 'shop',
            'targets' => 'Boosterino',
            'paymentType' => 'PC',
            'sum' => number_format($amount, 2, '.', ''),
            'label' => $label,
            'successURL' => $successUrl,
        ]);
        return 'https://yoomoney.ru/quickpay/confirm.xml?' . $q;
    }

    private function verify(array $d): bool
    {
        $secret = (new SettingsService())->get('yoomoney_secret');
        if ($secret === '') {
            return false;
        }
        if (!empty($d['sign'])) {
            return $this->verifySign($d, $secret);
        }
        if (!empty($d['sha1_hash'])) {
            return $this->verifySha1($d, $secret);
        }
        return false;
    }

    private function verifySign(array $d, string $secret): bool
    {
        $params = $d;
        unset($params['sign']);
        ksort($params, SORT_STRING);
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = $k . '=' . rawurlencode((string) $v);
        }
        $hash = hash_hmac('sha256', implode('&', $parts), $secret);
        return hash_equals($hash, (string) $d['sign']);
    }

    private function verifySha1(array $d, string $secret): bool
    {
        $str = implode('&', [
            $d['notification_type'] ?? '',
            $d['operation_id'] ?? '',
            $d['amount'] ?? '',
            $d['currency'] ?? '',
            $d['datetime'] ?? '',
            $d['sender'] ?? '',
            $d['codepro'] ?? '',
            $secret,
            $d['label'] ?? '',
        ]);
        return hash_equals(sha1($str), (string) $d['sha1_hash']);
    }

    private function credit(PDO $pdo, int $uid, float $sum, string $ref, int $refId): void
    {
        $st = $pdo->prepare('SELECT balance_rub FROM users WHERE id=:id FOR UPDATE');
        $st->execute(['id' => $uid]);
        $bal = (float) $st->fetchColumn() + $sum;
        $pdo->prepare('UPDATE users SET balance_rub=:b WHERE id=:id')->execute(['b' => $bal, 'id' => $uid]);
        $pdo->prepare('INSERT INTO balance_transactions (user_id,type,amount_rub,balance_after,reference_type,reference_id) VALUES (:u,:t,:a,:b,:rt,:ri)')
            ->execute(['u' => $uid, 't' => 'topup', 'a' => $sum, 'b' => $bal, 'rt' => $ref, 'ri' => $refId]);
    }
}
