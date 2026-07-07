<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $p, callable $h, array $mw = []): void { $this->add(['GET'], $p, $h, $mw); }
    public function post(string $p, callable $h, array $mw = []): void { $this->add(['POST'], $p, $h, $mw); }
    public function put(string $p, callable $h, array $mw = []): void { $this->add(['PUT'], $p, $h, $mw); }
    public function delete(string $p, callable $h, array $mw = []): void { $this->add(['DELETE'], $p, $h, $mw); }

    public function add(array $methods, string $path, callable $handler, array $mw = []): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
            'mw' => $mw,
        ];
    }

    public function dispatch(Request $req): void
    {
        foreach ($this->routes as $r) {
            if (!in_array($req->method(), $r['methods'], true)) {
                continue;
            }
            $params = $this->match($r['path'], $req->path());
            if ($params === null) {
                continue;
            }
            $h = $this->wrap($r['handler'], $r['mw']);
            $h($req, $params);
            return;
        }
        if ($req->isApi()) {
            Response::fail('not_found', 'Endpoint не найден.', 404);
        }
        Response::html(View::render('errors/404', ['title' => '404 - Boosterino']), 404);
    }

    private function wrap(callable $handler, array $mw): callable
    {
        $next = static fn (Request $r, array $p) => call_user_func($handler, $r, $p);
        foreach (array_reverse($mw) as $m) {
            $inst = is_object($m) ? $m : new $m();
            $prev = $next;
            $next = static fn (Request $r, array $p) => $inst->handle($r, $p, $prev);
        }
        return $next;
    }

    private function match(string $route, string $uri): ?array
    {
        $pat = '#^' . preg_replace('/\{(\w+)\}/', '(?<$1>[^/]+)', $route) . '$#';
        if (!preg_match($pat, $uri, $m)) {
            return null;
        }
        $out = [];
        foreach ($m as $k => $v) {
            if (!is_int($k)) {
                $out[$k] = $v;
            }
        }
        return $out;
    }
}
