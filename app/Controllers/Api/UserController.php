<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\PaymentService;
use PDO;

final class UserController
{
    public static function profile(Request $request): void
    {
        $user = (new AuthService())->user();
        Response::success(['user' => $user]);
    }

    public static function changePassword(Request $request): void
    {
        $user = (new AuthService())->user();
        try {
            (new AuthService())->changePassword(
                (int) $user['id'],
                (string) $request->input('current_password', ''),
                (string) $request->input('new_password', ''),
            );
            Response::success(['message' => 'Пароль изменён.']);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        }
    }

    public static function orders(Request $request): void
    {
        $user = (new AuthService())->user();
        $orders = (new OrderService())->listForUser((int) $user['id']);
        Response::success(['orders' => $orders]);
    }

    public static function createOrder(Request $request): void
    {
        $user = (new AuthService())->user();
        try {
            $result = (new OrderService())->create(
                (int) $user['id'],
                (int) $request->input('service_id', 0),
                (string) $request->input('link', ''),
                (int) $request->input('quantity', 0),
                (string) $request->input('payment_method', 'balance'),
            );
            Response::success($result, 201);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            Response::error('order_failed', $e->getMessage(), 500);
        }
    }

    public static function refill(Request $request, array $params): void
    {
        $user = (new AuthService())->user();
        try {
            $result = (new OrderService())->refill((int) $user['id'], (int) $params['id']);
            Response::success(['result' => $result]);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        }
    }

    public static function cancel(Request $request, array $params): void
    {
        $user = (new AuthService())->user();
        try {
            $result = (new OrderService())->cancel((int) $user['id'], (int) $params['id']);
            Response::success(['result' => $result]);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        }
    }

    public static function topup(Request $request): void
    {
        $user = (new AuthService())->user();
        try {
            $result = (new PaymentService())->createTopup(
                (int) $user['id'],
                (float) $request->input('amount', 0),
            );
            Response::success($result);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Response::error('payment_error', $e->getMessage(), 422);
        }
    }

    public static function transactions(Request $request): void
    {
        $user = (new AuthService())->user();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM balance_transactions WHERE user_id = :user_id ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute(['user_id' => $user['id']]);
        Response::success(['transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
