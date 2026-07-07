<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function success(mixed $data = null, int $status = 200): void
    {
        self::json(['success' => true, 'data' => $data], $status);
    }

    public static function error(string $code, string $message, int $status = 400): void
    {
        self::json([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }

    public static function noContent(): void
    {
        http_response_code(204);
    }
}
