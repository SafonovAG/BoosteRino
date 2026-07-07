<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class VerifiedEmailMiddleware extends Middleware
{
    public function handle(Request $request, array $params, callable $next): mixed
    {
        $auth = new AuthService();
        $user = $auth->user();

        if ($user === null) {
            Response::error('unauthorized', 'Authentication required.', 401);
            return null;
        }

        if ($user['email_verified_at'] === null) {
            Response::error('email_not_verified', 'Подтвердите email перед выполнением этого действия.', 403);
            return null;
        }

        return $next($request, $params);
    }
}
