<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\AdminDiagnosticsService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalog;
use App\Services\SettingsService;
use App\Services\TwiboostClient;
use App\Services\UserService;
use App\Services\AuthService;

final class AdminApi
{
    public static function dashboard(Request $r): void
    {
        $pdo = Database::pdo();

        $activeStatuses = "'pending','pending_payment','Awaiting','In progress','Partial'";

        $stats = [
            'generated_at' => date('c'),
            'users' => [
                'total' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
                'active' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND is_active=1")->fetchColumn(),
                'today' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND DATE(created_at)=CURDATE()")->fetchColumn(),
            ],
            'orders' => [
                'total' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                'today' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()')->fetchColumn(),
                'week' => (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
                'active' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ($activeStatuses)")->fetchColumn(),
            ],
            'revenue' => [
                'today' => (float) $pdo->query("SELECT COALESCE(SUM(cost_rub),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status NOT IN ('Canceled','Cancelled','Fail','Failed','Error')")->fetchColumn(),
                'week' => (float) $pdo->query("SELECT COALESCE(SUM(cost_rub),0) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status NOT IN ('Canceled','Cancelled','Fail','Failed','Error')")->fetchColumn(),
                'total' => (float) $pdo->query("SELECT COALESCE(SUM(cost_rub),0) FROM orders WHERE status NOT IN ('Canceled','Cancelled','Fail','Failed','Error')")->fetchColumn(),
            ],
            'services' => [
                'total' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
                'active' => (int) $pdo->query('SELECT COUNT(*) FROM services WHERE is_active=1')->fetchColumn(),
            ],
            'balances' => [
                'users_total' => (float) $pdo->query("SELECT COALESCE(SUM(balance_rub),0) FROM users WHERE role='user'")->fetchColumn(),
            ],
            'orders_by_status' => $pdo->query(
                'SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC'
            )->fetchAll(),
            'recent_orders' => $pdo->query(
                'SELECT o.id, o.status, o.cost_rub, o.created_at, u.email, u.id AS user_id, s.name AS service_name
                 FROM orders o
                 JOIN users u ON u.id = o.user_id
                 JOIN services s ON s.id = o.service_id
                 ORDER BY o.id DESC LIMIT 8'
            )->fetchAll(),
            'recent_users' => $pdo->query(
                "SELECT id, email, balance_rub, created_at FROM users WHERE role='user' ORDER BY id DESC LIMIT 6"
            )->fetchAll(),
        ];

        try {
            $tb = (new TwiboostClient())->balance();
            if (isset($tb['balance'])) {
                $tb['balance'] = round((float) $tb['balance'], 2);
            }
            $stats['twiboost'] = $tb;
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
        ServiceCatalog::ensureDescriptionColumn();
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT * FROM services ORDER BY category,name')->fetchAll();
        $price = new PricingService();
        $list = array_map(static function (array $s) use ($price): array {
            $fmt = $price->format($s);
            $s['price_per_thousand_rub'] = $fmt['price_per_thousand_rub'];
            $s['platform_name'] = $fmt['platform_name'];
            $s['category_label'] = $fmt['category_label'];
            return $s;
        }, $rows);
        Response::ok(['services' => $list, 'categories' => ServiceCatalog::adminCategories()]);
    }

    public static function serviceCategoriesGet(Request $r): void
    {
        Response::ok(['categories' => ServiceCatalog::adminCategories()]);
    }

    public static function serviceCategoriesPut(Request $r): void
    {
        try {
            $renamed = ServiceCatalog::adminRenameCategory(
                (string) $r->input('old_name', ''),
                (string) $r->input('new_name', '')
            );
            Response::ok(['renamed' => $renamed, 'categories' => ServiceCatalog::adminCategories()]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function serviceUpdate(Request $r, array $par): void
    {
        ServiceCatalog::ensureDescriptionColumn();
        try {
            $svc = ServiceCatalog::adminUpdate((int) $par['id'], $r->all());
            if (!$svc) {
                Response::fail('not_found', 'Услуга не найдена.', 404);
            }
            $fmt = (new PricingService())->format($svc);
            $svc['price_per_thousand_rub'] = $fmt['price_per_thousand_rub'];
            $svc['platform_name'] = $fmt['platform_name'];
            $svc['category_label'] = $fmt['category_label'];
            Response::ok(['service' => $svc]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
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
        $search = $r->query('q');
        Response::ok(['users' => (new UserService())->adminList($search ?: null)]);
    }

    public static function userShow(Request $r, array $par): void
    {
        $user = (new UserService())->adminGet((int) $par['id']);
        if (!$user) {
            Response::fail('not_found', 'Пользователь не найден.', 404);
        }
        Response::ok(['user' => $user]);
    }

    public static function userUpdate(Request $r, array $par): void
    {
        $auth = (new AuthService())->user();
        $canSetRole = ($auth['role'] ?? '') === 'superadmin';
        if (!$canSetRole && $r->input('role') !== null) {
            Response::fail('forbidden', 'Изменение роли доступно только superadmin.', 403);
        }
        try {
            $user = (new UserService())->adminUpdate((int) $par['id'], $r->all(), $canSetRole);
            Response::ok(['user' => $user]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function setUserRole(Request $r, array $par): void
    {
        self::userUpdate($r, $par);
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

    public static function orderDelete(Request $r, array $par): void
    {
        try {
            (new OrderService())->adminDelete((int) $par['id']);
            Response::ok(['deleted' => true]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
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
