<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ServiceCatalog;
use App\Services\SettingsService;
use App\Services\TwiboostClient;
use PDO;

final class AdminController
{
    public static function dashboard(Request $request): void
    {
        $pdo = Database::connection();
        $stats = [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'orders_today' => (int) $pdo->query(
                'SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'
            )->fetchColumn(),
            'total_user_balance' => (float) $pdo->query(
                'SELECT COALESCE(SUM(balance_rub), 0) FROM users'
            )->fetchColumn(),
        ];

        try {
            $balance = (new TwiboostClient())->balance();
            $stats['twiboost_balance'] = $balance;
        } catch (\Throwable $e) {
            $stats['twiboost_balance'] = ['error' => $e->getMessage()];
        }

        Response::success(['stats' => $stats]);
    }

    public static function settings(Request $request): void
    {
        if ($request->method() === 'GET') {
            Response::success(['settings' => (new SettingsService())->forAdmin()]);
            return;
        }

        $data = $request->all();
        $settings = new SettingsService();
        $allowed = [
            'app_url', 'app_secret', 'global_markup_percent', 'twiboost_api_key',
            'yoomoney_wallet', 'yoomoney_secret', 'mail_host', 'mail_port',
            'mail_user', 'mail_pass', 'mail_from', 'mail_from_name',
        ];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = (string) $data[$key];
            if ($value === '****') {
                continue;
            }
            $settings->set($key, $value);
        }

        Response::success(['settings' => $settings->forAdmin()]);
    }

    public static function markup(Request $request): void
    {
        $settings = new SettingsService();
        if ($request->method() === 'GET') {
            Response::success(['global_markup_percent' => $settings->getFloat('global_markup_percent', 30)]);
            return;
        }

        $settings->set('global_markup_percent', (string) $request->input('global_markup_percent', '30'));
        Response::success(['global_markup_percent' => $settings->getFloat('global_markup_percent')]);
    }

    public static function services(Request $request): void
    {
        $pdo = Database::connection();
        if ($request->method() === 'GET') {
            $services = $pdo->query('SELECT * FROM services ORDER BY category, name')->fetchAll(PDO::FETCH_ASSOC);
            Response::success(['services' => $services]);
            return;
        }

        $id = (int) $request->input('id', 0);
        $stmt = $pdo->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$service) {
            Response::error('not_found', 'Услуга не найдена.', 404);
            return;
        }

        if ($request->input('is_active') !== null) {
            $pdo->prepare('UPDATE services SET is_active = :active WHERE id = :id')
                ->execute(['active' => $request->input('is_active') ? 1 : 0, 'id' => $id]);
        }
        if ($request->input('markup_override') !== null) {
            $val = $request->input('markup_override');
            $pdo->prepare('UPDATE services SET markup_override = :markup WHERE id = :id')
                ->execute(['markup' => $val === '' ? null : $val, 'id' => $id]);
        }

        Response::success(['message' => 'Услуга обновлена.']);
    }

    public static function syncServices(Request $request): void
    {
        try {
            $count = ServiceCatalog::syncFromTwiboost();
            Response::success(['synced' => $count]);
        } catch (\Throwable $e) {
            Response::error('sync_failed', $e->getMessage(), 500);
        }
    }

    public static function twiboostBalance(Request $request): void
    {
        try {
            Response::success(['balance' => (new TwiboostClient())->balance()]);
        } catch (\Throwable $e) {
            Response::error('api_error', $e->getMessage(), 500);
        }
    }

    public static function users(Request $request): void
    {
        $pdo = Database::connection();
        $users = $pdo->query(
            'SELECT id, email, role, balance_rub, email_verified_at, is_active, created_at FROM users ORDER BY id DESC LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['users' => $users]);
    }

    public static function orders(Request $request): void
    {
        $pdo = Database::connection();
        $orders = $pdo->query(
            'SELECT o.*, u.email, s.name AS service_name FROM orders o
             JOIN users u ON u.id = o.user_id
             JOIN services s ON s.id = o.service_id
             ORDER BY o.id DESC LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['orders' => $orders]);
    }

    public static function admins(Request $request): void
    {
        $pdo = Database::connection();
        if ($request->method() === 'GET') {
            $admins = $pdo->query(
                "SELECT id, email, role, created_at FROM users WHERE role IN ('admin', 'superadmin') ORDER BY id"
            )->fetchAll(PDO::FETCH_ASSOC);
            Response::success(['admins' => $admins]);
            return;
        }

        if ($request->method() === 'POST') {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $role = (string) $request->input('role', 'admin');
            if (!in_array($role, ['admin', 'superadmin'], true)) {
                Response::error('validation_error', 'Некорректная роль.', 422);
                return;
            }
            $password = (string) $request->input('password', '');
            if (strlen($password) < 8) {
                Response::error('validation_error', 'Пароль минимум 8 символов.', 422);
                return;
            }
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            try {
                $pdo->prepare(
                    'INSERT INTO users (email, password_hash, role, email_verified_at) VALUES (:email, :hash, :role, NOW())'
                )->execute(['email' => $email, 'hash' => $hash, 'role' => $role]);
                Response::success(['message' => 'Администратор создан.'], 201);
            } catch (\PDOException) {
                Response::error('duplicate', 'Email уже существует.', 422);
            }
            return;
        }

        if ($request->method() === 'DELETE') {
            $id = (int) $request->input('id', 0);
            $pdo->prepare("UPDATE users SET role = 'user' WHERE id = :id AND role = 'admin'")
                ->execute(['id' => $id]);
            Response::success(['message' => 'Права администратора сняты.']);
        }
    }
}

final class PaymentNotifyController
{
    public static function yoomoney(Request $request): void
    {
        $data = $request->all();
        if ($data === [] && !empty($_POST)) {
            $data = $_POST;
        }

        $ok = (new PaymentService())->handleNotification($data);
        if (!$ok) {
            Response::error('invalid_notification', 'Invalid notification.', 400);
            return;
        }
        Response::success(['ok' => true]);
    }
}
