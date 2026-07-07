<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthApi
{
    public static function register(Request $r): void
    {
        try {
            (new AuthService())->register((string) $r->input('email', ''), (string) $r->input('password', ''));
            Response::ok(['message' => 'Проверьте email.'], 201);
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function login(Request $r): void
    {
        try {
            Response::ok(['user' => (new AuthService())->login((string) $r->input('email', ''), (string) $r->input('password', ''))]);
        } catch (\InvalidArgumentException $e) {
            Response::fail('auth', $e->getMessage(), 401);
        }
    }

    public static function logout(Request $r): void
    {
        (new AuthService())->logout();
        Response::ok();
    }

    public static function forgot(Request $r): void
    {
        (new AuthService())->forgot((string) $r->input('email', ''));
        Response::ok(['message' => 'Если email есть в системе, письмо отправлено.']);
    }

    public static function reset(Request $r): void
    {
        try {
            if (!(new AuthService())->reset((string) $r->input('token', ''), (string) $r->input('password', ''))) {
                Response::fail('token', 'Ссылка недействительна.', 400);
            }
            Response::ok();
        } catch (\InvalidArgumentException $e) {
            Response::fail('validation', $e->getMessage(), 422);
        }
    }

    public static function verifyEmail(Request $r): void
    {
        Response::ok(['verified' => (new AuthService())->verifyEmail((string) $r->query('token', ''))]);
    }
}
