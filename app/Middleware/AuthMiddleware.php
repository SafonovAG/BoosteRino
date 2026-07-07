<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthMiddleware extends Middleware
{
    public function handle(Request $req, array $params, callable $next): mixed
    {
        if (!(new AuthService())->user()) {
            if ($req->isApi()) {
                Response::fail('unauthorized', 'Требуется авторизация.', 401);
            }
            Response::redirect('/login');
        }
        return $next($req, $params);
    }
}

final class VerifiedMiddleware extends Middleware
{
    public function handle(Request $req, array $params, callable $next): mixed
    {
        $u = (new AuthService())->user();
        if (!$u || !$u['email_verified_at']) {
            Response::fail('email_not_verified', 'Подтвердите email перед заказом.', 403);
        }
        return $next($req, $params);
    }
}

final class AdminMiddleware extends Middleware
{
    public function __construct(private readonly bool $superOnly = false) {}

    public function handle(Request $req, array $params, callable $next): mixed
    {
        $u = (new AuthService())->user();
        $ok = $u && ($this->superOnly
            ? $u['role'] === 'superadmin'
            : in_array($u['role'], ['admin', 'superadmin'], true));
        if (!$ok) {
            Response::fail('forbidden', 'Доступ запрещён.', 403);
        }
        return $next($req, $params);
    }
}
