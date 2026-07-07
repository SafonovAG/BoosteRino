<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

abstract class Middleware
{
    abstract public function handle(Request $request, array $params, callable $next): mixed;
}

final class CsrfMiddleware extends Middleware
{
    public function handle(Request $request, array $params, callable $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->header('X-CSRF-Token') ?? $request->input('_csrf');
            if (!Session::validateCsrf(is_string($token) ? $token : null)) {
                if ($request->isApi()) {
                    Response::error('csrf_invalid', 'Invalid CSRF token.', 419);
                    return null;
                }
                Response::error('csrf_invalid', 'Invalid CSRF token.', 419);
                return null;
            }
        }

        return $next($request, $params);
    }
}
