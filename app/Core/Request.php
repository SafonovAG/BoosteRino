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
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        $body = [];
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_starts_with($ct, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        } elseif (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $body = $_POST;
        }

        return new self($method, $path, $_GET, $body, $_SERVER);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function query(string $k, mixed $d = null): mixed { return $this->query[$k] ?? $d; }
    public function input(string $k, mixed $d = null): mixed { return $this->body[$k] ?? $d; }
    public function all(): array { return $this->body; }
    public function ip(): string { return $this->server['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function isApi(): bool { return str_starts_with($this->path, '/api/'); }
    public function header(string $n, mixed $d = null): mixed
    {
        $k = 'HTTP_' . strtoupper(str_replace('-', '_', $n));
        return $this->server[$k] ?? $d;
    }
}
