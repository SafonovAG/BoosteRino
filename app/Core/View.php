<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $tpl, array $vars = []): string
    {
        $file = BASE_PATH . '/views/' . $tpl . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Шаблон не найден: {$tpl}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
