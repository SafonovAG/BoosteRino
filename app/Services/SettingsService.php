<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class SettingsService
{
    private static ?array $cache = null;
    private const SECRET = ['app_secret', 'twiboost_api_key', 'yoomoney_secret', 'mail_pass'];

    public function get(string $key, string $default = ''): string
    {
        $this->load();
        return self::$cache[$key] ?? $default;
    }

    public function getFloat(string $key, float $def = 0): float
    {
        return (float) $this->get($key, (string) $def);
    }

    public function set(string $key, string $value): void
    {
        $sens = in_array($key, self::SECRET, true) ? 1 : 0;
        Database::pdo()->prepare(
            'INSERT INTO settings (setting_key, setting_value, is_sensitive) VALUES (:k,:v,:s)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), is_sensitive=VALUES(is_sensitive)'
        )->execute(['k' => $key, 'v' => $value, 's' => $sens]);
        self::$cache = null;
    }

    public function forAdmin(): array
    {
        $this->load();
        $out = [];
        foreach (self::$cache ?? [] as $k => $v) {
            $sec = in_array($k, self::SECRET, true);
            $out[$k] = ['value' => ($sec && $v !== '') ? '****' : $v, 'is_set' => $v !== '', 'is_sensitive' => $sec];
        }
        return $out;
    }

    public function ensureSecret(): void
    {
        if ($this->get('app_secret') === '') {
            $this->set('app_secret', bin2hex(random_bytes(32)));
        }
    }

    private function load(): void
    {
        if (self::$cache !== null) {
            return;
        }
        $rows = Database::pdo()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_ASSOC);
        self::$cache = [];
        foreach ($rows as $r) {
            self::$cache[$r['setting_key']] = (string) $r['setting_value'];
        }
    }
}
