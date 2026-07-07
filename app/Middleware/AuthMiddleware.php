<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthMiddleware extends Middleware
{
    public function handle(Request $request, array $params, callable $next): mixed
    {
        $auth = new AuthService();
        $user = $auth->user();

        if ($user === null) {
            if ($request->isApi()) {
                Response::error('unauthorized', 'Authentication required.', 401);
                return null;
            }
            Response::redirect('/login');
            return null;
        }

        Session::set('current_user', $user);

        return $next($request, $params);
    }
}
