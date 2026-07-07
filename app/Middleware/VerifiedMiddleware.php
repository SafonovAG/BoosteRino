<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

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
