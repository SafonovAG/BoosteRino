<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
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
        Response::ok(['settings' => (new SettingsService())->forAdmin()]);
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

    public static function orders(Request $r): void
    {
        $sql = 'SELECT o.*,u.email,s.name service_name FROM orders o JOIN users u ON u.id=o.user_id JOIN services s ON s.id=o.service_id ORDER BY o.id DESC LIMIT 200';
        Response::ok(['orders' => Database::pdo()->query($sql)->fetchAll()]);
    }
}
