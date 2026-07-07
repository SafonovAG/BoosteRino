<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

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
