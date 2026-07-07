<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok(mixed $data = null, int $code = 200): void
    {
        self::json(['success' => true, 'data' => $data], $code);
    }

    public static function fail(string $code, string $msg, int $http = 400): void
    {
        self::json(['success' => false, 'error' => ['code' => $code, 'message' => $msg]], $http);
    }

    public static function html(string $html, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function okEmpty(): void
    {
        http_response_code(200);
        exit;
    }
}
