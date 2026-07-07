<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AdminMiddleware extends Middleware
{
    public function __construct(
        private readonly bool $superadminOnly = false,
    ) {
    }

    public function handle(Request $request, array $params, callable $next): mixed
    {
        $auth = new AuthService();
        $user = $auth->user();

        if ($user === null) {
            Response::error('unauthorized', 'Authentication required.', 401);
            return null;
        }

        $role = $user['role'];
        $allowed = $this->superadminOnly
            ? $role === 'superadmin'
            : in_array($role, ['admin', 'superadmin'], true);

        if (!$allowed) {
            Response::error('forbidden', 'Access denied.', 403);
            return null;
        }

        return $next($request, $params);
    }
}
