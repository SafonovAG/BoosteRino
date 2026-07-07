<?php

declare(strict_types=1);

/**
 * PSR-4 autoload без Composer - для хостинга с PHP 8.3 + MySQL.
 */
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, 4));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});
