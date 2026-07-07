<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\BalanceTransactionType;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\RuDate;

final class UserApi
{
    public static function profile(Request $r): void
    {
        Response::ok(['user' => (new AuthService())->user()]);
    }

    public static function changePassword(Request $r): void
    {
        $u = (new AuthService())->user();
        try {
            (new AuthService())->changePassword(
                (int) $u['id'],
                (string) $r->input('current_password', ''),
                (string) $r->input('new_password', '')
            );
            Response::ok();
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function orders(Request $r): void
    {
        $u = (new AuthService())->user();
        Response::ok(['orders' => (new OrderService())->list((int) $u['id'])]);
    }

    public static function createOrder(Request $r): void
    {
        $u = (new AuthService())->user();
        try {
            $res = (new OrderService())->create(
                (int) $u['id'], (int) $r->input('service_id', 0),
                (string) $r->input('link', ''), (int) $r->input('quantity', 0),
                (string) $r->input('payment_method', 'balance')
            );
            if (!isset($res['payment_url']) && isset($res['id'])) {
                $res = ['order' => $res];
            }
            Response::ok($res, 201);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            Response::fail('order', $e->getMessage(), 500);
        }
    }

    public static function orderShow(Request $r, array $par): void
    {
        $u = (new AuthService())->user();
        $order = (new OrderService())->getDetailForUser((int) $u['id'], (int) $par['id'], true);
        if (!$order) {
            Response::fail('not_found', 'Заказ не найден.', 404);
        }
        Response::ok(['order' => $order]);
    }

    public static function ordersBatch(Request $r): void
    {
        $u = (new AuthService())->user();
        $raw = (string) $r->query('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        Response::ok(['orders' => (new OrderService())->listDetailsForUser((int) $u['id'], $ids)]);
    }

    public static function orderSync(Request $r, array $par): void
    {
        $u = (new AuthService())->user();
        $order = (new OrderService())->getDetailForUser((int) $u['id'], (int) $par['id'], true);
        if (!$order) {
            Response::fail('not_found', 'Заказ не найден.', 404);
        }
        Response::ok(['order' => $order]);
    }

    public static function refill(Request $r, array $par): void
    {
        $u = (new AuthService())->user();
        try {
            Response::ok(['result' => (new OrderService())->refill((int) $u['id'], (int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function cancel(Request $r, array $par): void
    {
        $u = (new AuthService())->user();
        try {
            Response::ok(['result' => (new OrderService())->cancel((int) $u['id'], (int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function topup(Request $r): void
    {
        $u = (new AuthService())->user();
        try {
            Response::ok((new PaymentService())->topup((int) $u['id'], (float) $r->input('amount', 0)));
        } catch (\Throwable $e) {
            Response::fail('pay', $e->getMessage(), 422);
        }
    }

    public static function tx(Request $r): void
    {
        $u = (new AuthService())->user();
        $st = Database::pdo()->prepare('SELECT * FROM balance_transactions WHERE user_id=:u ORDER BY id DESC LIMIT 50');
        $st->execute(['u' => $u['id']]);
        $rows = $st->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $row['type_label'] = BalanceTransactionType::label((string) $row['type']);
            $row['created_at_formatted'] = RuDate::format((string) ($row['created_at'] ?? ''));
            $out[] = $row;
        }
        Response::ok(['transactions' => $out]);
    }
}
