<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;

final class App
{
    public static function run(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        Session::start();
        try {
            (new SettingsService())->ensureSecret();
        } catch (\Throwable) {
        }
        $router = new Router();
        (require BASE_PATH . '/config/routes.php')($router);
        $router->dispatch(Request::capture());
    }
}
