<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class UserService
{
    public function adminList(?string $search = null, int $limit = 300): array
    {
        $sql = 'SELECT id, email, role, balance_rub, email_verified_at, is_active, created_at, updated_at
                FROM users WHERE 1=1';
        $params = [];
        if ($search !== null && $search !== '') {
            $sql .= ' AND (id = :id OR email LIKE :q)';
            $params['q'] = '%' . $search . '%';
            $params['id'] = is_numeric($search) ? (int) $search : 0;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . max(1, min(500, $limit));
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adminGet(int $id): ?array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id, email, role, balance_rub, email_verified_at, is_active, created_at, updated_at FROM users WHERE id=:id');
        $st->execute(['id' => $id]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return null;
        }

        $orders = $pdo->prepare(
            'SELECT o.id, o.status, o.quantity, o.cost_rub, o.payment_method, o.link, o.twiboost_order_id,
                    o.created_at, o.updated_at, s.name AS service_name, s.id AS service_id, s.category
             FROM orders o
             JOIN services s ON s.id = o.service_id
             WHERE o.user_id = :u
             ORDER BY o.id DESC
             LIMIT 150'
        );
        $orders->execute(['u' => $id]);
        $user['orders'] = $orders->fetchAll(PDO::FETCH_ASSOC);

        $tx = $pdo->prepare(
            'SELECT id, type, amount_rub, balance_after, reference_type, reference_id, created_at
             FROM balance_transactions
             WHERE user_id = :u
             ORDER BY id DESC
             LIMIT 80'
        );
        $tx->execute(['u' => $id]);
        $user['transactions'] = $tx->fetchAll(PDO::FETCH_ASSOC);

        $pay = $pdo->prepare(
            'SELECT id, type, amount_rub, status, order_id, label, yoomoney_operation_id, created_at
             FROM payments
             WHERE user_id = :u
             ORDER BY id DESC
             LIMIT 80'
        );
        $pay->execute(['u' => $id]);
        $user['payments'] = $pay->fetchAll(PDO::FETCH_ASSOC);

        $stats = $pdo->prepare(
            'SELECT COUNT(*) AS order_count, COALESCE(SUM(cost_rub), 0) AS total_spent
             FROM orders WHERE user_id = :u'
        );
        $stats->execute(['u' => $id]);
        $user['stats'] = $stats->fetch(PDO::FETCH_ASSOC) ?: ['order_count' => 0, 'total_spent' => 0];

        return $user;
    }

    public function adminUpdate(int $id, array $data, bool $canSetRole): array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT * FROM users WHERE id=:id');
        $st->execute(['id' => $id]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new \InvalidArgumentException('Пользователь не найден.');
        }

        $pdo->beginTransaction();
        try {
            if ($canSetRole && array_key_exists('role', $data)) {
                $role = (string) $data['role'];
                if (!in_array($role, ['user', 'admin', 'superadmin'], true)) {
                    throw new \InvalidArgumentException('Неверная роль.');
                }
                $pdo->prepare('UPDATE users SET role=:r, updated_at=NOW() WHERE id=:id')
                    ->execute(['r' => $role, 'id' => $id]);
            }

            if (array_key_exists('is_active', $data)) {
                $active = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($active === null) {
                    throw new \InvalidArgumentException('Неверное значение is_active.');
                }
                $pdo->prepare('UPDATE users SET is_active=:a, updated_at=NOW() WHERE id=:id')
                    ->execute(['a' => $active ? 1 : 0, 'id' => $id]);
            }

            if (array_key_exists('balance_rub', $data) && $data['balance_rub'] !== '' && $data['balance_rub'] !== null) {
                $newBal = round((float) $data['balance_rub'], 2);
                if ($newBal < 0) {
                    throw new \InvalidArgumentException('Баланс не может быть отрицательным.');
                }
                $oldBal = (float) $user['balance_rub'];
                if (abs($newBal - $oldBal) > 0.001) {
                    $pdo->prepare('UPDATE users SET balance_rub=:b, updated_at=NOW() WHERE id=:id')
                        ->execute(['b' => $newBal, 'id' => $id]);
                    $pdo->prepare(
                        'INSERT INTO balance_transactions (user_id, type, amount_rub, balance_after, reference_type, reference_id)
                         VALUES (:u, :t, :a, :b, \'admin\', NULL)'
                    )->execute([
                        'u' => $id,
                        't' => 'admin_adjust',
                        'a' => $newBal - $oldBal,
                        'b' => $newBal,
                    ]);
                }
            } elseif (array_key_exists('balance_delta', $data) && $data['balance_delta'] !== '' && $data['balance_delta'] !== null) {
                $delta = round((float) $data['balance_delta'], 2);
                if ($delta !== 0.0) {
                    $stBal = $pdo->prepare('SELECT balance_rub FROM users WHERE id=:id FOR UPDATE');
                    $stBal->execute(['id' => $id]);
                    $oldBal = (float) $stBal->fetchColumn();
                    $newBal = round($oldBal + $delta, 2);
                    if ($newBal < 0) {
                        throw new \InvalidArgumentException('Баланс не может быть отрицательным.');
                    }
                    $pdo->prepare('UPDATE users SET balance_rub=:b, updated_at=NOW() WHERE id=:id')
                        ->execute(['b' => $newBal, 'id' => $id]);
                    $pdo->prepare(
                        'INSERT INTO balance_transactions (user_id, type, amount_rub, balance_after, reference_type, reference_id)
                         VALUES (:u, :t, :a, :b, \'admin\', NULL)'
                    )->execute([
                        'u' => $id,
                        't' => 'admin_adjust',
                        'a' => $delta,
                        'b' => $newBal,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->adminGet($id) ?? [];
    }
}
