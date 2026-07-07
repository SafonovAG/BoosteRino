<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }

    public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, mixed $v): void { $_SESSION[$k] = $v; }
    public static function forget(string $k): void { unset($_SESSION[$k]); }
    public static function regen(): void { session_regenerate_id(true); }

    public static function csrf(): string
    {
        if (!self::get('_csrf')) {
            self::set('_csrf', bin2hex(random_bytes(32)));
        }
        return (string) self::get('_csrf');
    }

    public static function checkCsrf(?string $t): bool
    {
        $s = self::get('_csrf');
        return is_string($s) && is_string($t) && hash_equals($s, $t);
    }
}
