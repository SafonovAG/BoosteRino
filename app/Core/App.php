<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;

final class App
{
    public static function run(): void
    {
        self::sendSecurityHeaders();
        Session::start();

        try {
            (new SettingsService())->ensureAppSecret();
        } catch (\Throwable) {
            // DB may not be ready during install
        }

        $router = new Router();
        $register = require dirname(__DIR__, 2) . '/config/routes.php';
        $register($router);

        $request = Request::capture();
        $router->dispatch($request);
    }

    private static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}
