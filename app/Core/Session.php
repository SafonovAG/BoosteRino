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

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function csrfToken(): string
    {
        if (!self::get('_csrf')) {
            self::set('_csrf', bin2hex(random_bytes(32)));
        }

        return (string) self::get('_csrf');
    }

    public static function validateCsrf(?string $token): bool
    {
        $sessionToken = self::get('_csrf');

        return is_string($sessionToken)
            && is_string($token)
            && hash_equals($sessionToken, $token);
    }
}
