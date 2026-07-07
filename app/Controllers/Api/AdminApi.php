<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AdminDiagnosticsService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ServiceCatalog;
use App\Services\SettingsService;
use App\Services\TwiboostClient;

final class AdminApi
{
    public static function dashboard(Request $r): void
    {
        $pdo = Database::pdo();
        $stats = [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'orders_today' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
        ];
        try {
            $stats['twiboost'] = (new TwiboostClient())->balance();
        } catch (\Throwable $e) {
            $stats['twiboost_error'] = $e->getMessage();
        }
        Response::ok(['stats' => $stats]);
    }

    public static function settingsGet(Request $r): void
    {
        Response::ok([
            'settings' => (new SettingsService())->forAdmin(),
            'yoomoney_notify_url' => (new PaymentService())->notifyUrl(),
        ]);
    }

    public static function settingsPut(Request $r): void
    {
        $s = new SettingsService();
        foreach ($r->all() as $k => $v) {
            if ($v === '****') {
                continue;
            }
            $s->set((string) $k, (string) $v);
        }
        Response::ok(['settings' => $s->forAdmin()]);
    }

    public static function servicesGet(Request $r): void
    {
        Response::ok(['services' => Database::pdo()->query('SELECT * FROM services ORDER BY category,name')->fetchAll()]);
    }

    public static function servicesPut(Request $r): void
    {
        $id = (int) $r->input('id', 0);
        $pdo = Database::pdo();
        if ($r->input('is_active') !== null) {
            $pdo->prepare('UPDATE services SET is_active=:a WHERE id=:id')->execute(['a' => $r->input('is_active') ? 1 : 0, 'id' => $id]);
        }
        if ($r->input('markup_override') !== null) {
            $m = $r->input('markup_override');
            $pdo->prepare('UPDATE services SET markup_override=:m WHERE id=:id')->execute(['m' => $m === '' ? null : $m, 'id' => $id]);
        }
        Response::ok();
    }

    public static function sync(Request $r): void
    {
        try {
            Response::ok(['synced' => ServiceCatalog::sync()]);
        } catch (\Throwable $e) {
            Response::fail('sync', $e->getMessage(), 500);
        }
    }

    public static function users(Request $r): void
    {
        Response::ok(['users' => Database::pdo()->query('SELECT id,email,role,balance_rub,email_verified_at,created_at FROM users ORDER BY id DESC LIMIT 200')->fetchAll()]);
    }

    public static function setUserRole(Request $r, array $par): void
    {
        $role = (string) $r->input('role', '');
        if (!in_array($role, ['user', 'admin', 'superadmin'], true)) {
            Response::fail('validation', 'Неверная роль.', 422);
        }
        $id = (int) $par['id'];
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id,role FROM users WHERE id=:id');
        $st->execute(['id' => $id]);
        $u = $st->fetch();
        if (!$u) {
            Response::fail('not_found', 'Пользователь не найден.', 404);
        }
        $pdo->prepare('UPDATE users SET role=:r WHERE id=:id')->execute(['r' => $role, 'id' => $id]);
        Response::ok();
    }

    public static function orders(Request $r): void
    {
        $status = $r->query('status');
        $search = $r->query('q');
        Response::ok(['orders' => (new OrderService())->adminList($status ?: null, $search ?: null)]);
    }

    public static function orderShow(Request $r, array $par): void
    {
        $o = (new OrderService())->adminGet((int) $par['id']);
        if (!$o) {
            Response::fail('not_found', 'Заказ не найден.', 404);
        }
        Response::ok(['order' => $o]);
    }

    public static function orderUpdate(Request $r, array $par): void
    {
        try {
            (new OrderService())->adminUpdateStatus((int) $par['id'], (string) $r->input('status', ''));
            Response::ok(['order' => (new OrderService())->adminGet((int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function orderSync(Request $r, array $par): void
    {
        try {
            Response::ok(['order' => (new OrderService())->adminSyncOne((int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Response::fail('sync', $e->getMessage(), 500);
        }
    }

    public static function ordersSyncAll(Request $r): void
    {
        try {
            Response::ok(['updated' => (new OrderService())->sync()]);
        } catch (\Throwable $e) {
            Response::fail('sync', $e->getMessage(), 500);
        }
    }

    public static function orderRefill(Request $r, array $par): void
    {
        try {
            Response::ok(['result' => (new OrderService())->adminRefill((int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Response::fail('refill', $e->getMessage(), 500);
        }
    }

    public static function orderCancel(Request $r, array $par): void
    {
        try {
            Response::ok(['result' => (new OrderService())->adminCancel((int) $par['id'])]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        } catch (\Throwable $e) {
            Response::fail('cancel', $e->getMessage(), 500);
        }
    }

    public static function diagnosticsRun(Request $r): void
    {
        Response::ok(['results' => (new AdminDiagnosticsService())->runAll()]);
    }

    public static function diagnosticsSupplier(Request $r): void
    {
        Response::ok(['results' => (new AdminDiagnosticsService())->runSupplier()]);
    }

    public static function diagnosticsApiProbe(Request $r): void
    {
        Response::ok(['message' => 'Admin API доступен', 'time' => date('c')]);
    }
}
