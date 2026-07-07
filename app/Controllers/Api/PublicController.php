<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Services\AuthService;
use App\Services\PricingService;
use App\Services\ServiceCatalog;

final class AuthController
{
    public static function register(Request $request): void
    {
        try {
            $result = (new AuthService())->register(
                (string) $request->input('email', ''),
                (string) $request->input('password', ''),
            );
            Response::success(['message' => 'Регистрация успешна. Проверьте email.', 'user' => $result], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        }
    }

    public static function login(Request $request): void
    {
        try {
            $user = (new AuthService())->login(
                (string) $request->input('email', ''),
                (string) $request->input('password', ''),
            );
            Response::success(['user' => $user]);
        } catch (\InvalidArgumentException $e) {
            Response::error('auth_failed', $e->getMessage(), 401);
        }
    }

    public static function logout(Request $request): void
    {
        (new AuthService())->logout();
        Response::success(['message' => 'Вы вышли из системы.']);
    }

    public static function forgotPassword(Request $request): void
    {
        (new AuthService())->forgotPassword((string) $request->input('email', ''));
        Response::success(['message' => 'Если email зарегистрирован, мы отправили инструкции.']);
    }

    public static function resetPassword(Request $request): void
    {
        try {
            $ok = (new AuthService())->resetPassword(
                (string) $request->input('token', ''),
                (string) $request->input('password', ''),
            );
            if (!$ok) {
                Response::error('invalid_token', 'Ссылка недействительна или устарела.', 400);
                return;
            }
            Response::success(['message' => 'Пароль обновлён.']);
        } catch (\InvalidArgumentException $e) {
            Response::error('validation_error', $e->getMessage(), 422);
        }
    }

    public static function verifyEmail(Request $request): void
    {
        $token = (string) $request->query('token', '');
        $ok = (new AuthService())->verifyEmail($token);
        if (!$ok) {
            Response::error('invalid_token', 'Ссылка недействительна или устарела.', 400);
            return;
        }
        Response::success(['message' => 'Email подтверждён.']);
    }
}

final class ServiceController
{
    public static function index(Request $request): void
    {
        $pricing = new PricingService();
        $services = array_map(
            static fn (array $s) => $pricing->formatService($s),
            ServiceCatalog::activeList()
        );
        Response::success(['services' => $services]);
    }

    public static function show(Request $request, array $params): void
    {
        $service = ServiceCatalog::find((int) $params['id']);
        if (!$service || !(int) $service['is_active']) {
            Response::error('not_found', 'Услуга не найдена.', 404);
            return;
        }

        $quantity = max(1, (int) $request->query('quantity', (string) $service['min_qty']));
        $pricing = new PricingService();
        $formatted = $pricing->formatService($service);
        $formatted['price_rub'] = $pricing->forService($service, $quantity);
        $formatted['quantity'] = $quantity;

        Response::success(['service' => $formatted]);
    }
}
