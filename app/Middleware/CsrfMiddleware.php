<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class CsrfMiddleware extends Middleware
{
    public function handle(Request $req, array $params, callable $next): mixed
    {
        if (in_array($req->method(), ['POST', 'PUT', 'DELETE'], true)) {
            $t = $req->header('X-CSRF-Token') ?? $req->input('_csrf');
            if (!Session::checkCsrf(is_string($t) ? $t : null)) {
                Response::fail('csrf', 'Неверный CSRF-токен.', 419);
            }
        }
        return $next($req, $params);
    }
}
