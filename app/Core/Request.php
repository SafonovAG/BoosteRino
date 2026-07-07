<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $cookies,
        private readonly array $files,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $body = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_starts_with($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $body = $_POST;
        }

        return new self($method, $path, $_GET, $body, $_SERVER, $_COOKIE, $_FILES);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->body;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->server('REMOTE_ADDR', '0.0.0.0');
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    public function bearerToken(): ?string
    {
        $header = $this->server('HTTP_AUTHORIZATION');
        if ($header && preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $this->server[$key] ?? $default;
    }
}
