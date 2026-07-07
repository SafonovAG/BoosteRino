<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): string
    {
        $path = dirname(__DIR__, 2) . '/views/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View [{$template}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $path;

        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
