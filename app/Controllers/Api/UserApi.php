<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\PaymentService;

final class UserApi
{
    public static function profile(Request $r): void
    {
        Response::ok(['user' => (new AuthService())->user()]);
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
            Response::ok($res, 201);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            Response::fail('order', $e->getMessage(), 500);
        }
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
        Response::ok(['transactions' => $st->fetchAll()]);
    }
}
