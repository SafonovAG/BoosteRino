<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: string[], path: string, handler: callable, middleware: string[]}> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add(['GET'], $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add(['POST'], $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->add(['PUT'], $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->add(['DELETE'], $path, $handler, $middleware);
    }

    public function add(array $methods, string $path, callable $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if (!in_array($request->method(), $route['methods'], true)) {
                continue;
            }

            $params = $this->match($route['path'], $request->path());
            if ($params === null) {
                continue;
            }

            $handler = $this->wrapMiddleware(
                static function (Request $req, array $p) use ($route) {
                    return call_user_func($route['handler'], $req, $p);
                },
                $route['middleware']
            );
            $handler($request, $params);
            return;
        }

        if ($request->isApi()) {
            Response::error('not_found', 'Endpoint not found.', 404);
        } else {
            Response::html(View::render('errors/404', ['title' => 'Страница не найдена']), 404);
        }
    }

    private function wrapMiddleware(callable $handler, array $middleware): callable
    {
        return array_reduce(
            array_reverse($middleware),
            static function (callable $next, string|object $mw) {
                return static function (Request $request, array $params = []) use ($next, $mw) {
                    $instance = is_object($mw) ? $mw : new $mw();
                    return $instance->handle($request, $params, $next);
                };
            },
            $handler
        );
    }

    private function match(string $routePath, string $requestPath): ?array
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestPath, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
