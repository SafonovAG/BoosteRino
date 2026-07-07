<?php

declare(strict_types=1);

use App\Controllers\Api\AdminController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\PaymentNotifyController;
use App\Controllers\Api\ServiceController;
use App\Controllers\Api\UserController;
use App\Controllers\Web\PageController;
use App\Core\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\VerifiedEmailMiddleware;

return static function (Router $router): void {
    $csrf = CsrfMiddleware::class;
    $auth = AuthMiddleware::class;
    $verified = VerifiedEmailMiddleware::class;
    $admin = AdminMiddleware::class;
    $superadmin = fn () => new AdminMiddleware(true);

    // Web pages
    $router->get('/', [PageController::class, 'home']);
    $router->get('/services', [PageController::class, 'services']);
    $router->get('/login', [PageController::class, 'login']);
    $router->get('/register', [PageController::class, 'register']);
    $router->get('/forgot-password', [PageController::class, 'forgotPassword']);
    $router->get('/reset-password', [PageController::class, 'resetPassword']);
    $router->get('/verify-email', [PageController::class, 'verifyEmail']);
    $router->get('/cabinet', [PageController::class, 'cabinet']);
    $router->get('/admin', [PageController::class, 'admin']);

    // Public API
    $router->get('/api/v1/services', [ServiceController::class, 'index']);
    $router->get('/api/v1/services/{id}', [ServiceController::class, 'show']);

    $router->post('/api/v1/auth/register', [AuthController::class, 'register'], [
        $csrf, new RateLimitMiddleware('register'),
    ]);
    $router->post('/api/v1/auth/login', [AuthController::class, 'login'], [
        $csrf, new RateLimitMiddleware('login'),
    ]);
    $router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], [$csrf, $auth]);
    $router->post('/api/v1/auth/forgot-password', [AuthController::class, 'forgotPassword'], [
        $csrf, new RateLimitMiddleware('forgot'),
    ]);
    $router->post('/api/v1/auth/reset-password', [AuthController::class, 'resetPassword'], [$csrf]);
    $router->get('/api/v1/auth/verify-email', [AuthController::class, 'verifyEmail']);

    // User API
    $router->get('/api/v1/user/profile', [UserController::class, 'profile'], [$auth]);
    $router->post('/api/v1/user/change-password', [UserController::class, 'changePassword'], [$csrf, $auth]);
    $router->get('/api/v1/user/orders', [UserController::class, 'orders'], [$auth]);
    $router->post('/api/v1/user/orders', [UserController::class, 'createOrder'], [$csrf, $auth, $verified]);
    $router->post('/api/v1/user/orders/{id}/refill', [UserController::class, 'refill'], [$csrf, $auth, $verified]);
    $router->post('/api/v1/user/orders/{id}/cancel', [UserController::class, 'cancel'], [$csrf, $auth, $verified]);
    $router->post('/api/v1/user/balance/topup', [UserController::class, 'topup'], [$csrf, $auth, $verified]);
    $router->get('/api/v1/user/transactions', [UserController::class, 'transactions'], [$auth]);

    // Admin API
    $router->get('/api/v1/admin/dashboard', [AdminController::class, 'dashboard'], [$auth, $admin]);
    $router->get('/api/v1/admin/settings', [AdminController::class, 'settings'], [$auth, $superadmin()]);
    $router->put('/api/v1/admin/settings', [AdminController::class, 'settings'], [$csrf, $auth, $superadmin()]);
    $router->get('/api/v1/admin/settings/markup', [AdminController::class, 'markup'], [$auth, $superadmin()]);
    $router->put('/api/v1/admin/settings/markup', [AdminController::class, 'markup'], [$csrf, $auth, $superadmin()]);
    $router->get('/api/v1/admin/services', [AdminController::class, 'services'], [$auth, $admin]);
    $router->put('/api/v1/admin/services', [AdminController::class, 'services'], [$csrf, $auth, $admin]);
    $router->post('/api/v1/admin/services/sync', [AdminController::class, 'syncServices'], [$csrf, $auth, $admin]);
    $router->get('/api/v1/admin/twiboost/balance', [AdminController::class, 'twiboostBalance'], [$auth, $admin]);
    $router->get('/api/v1/admin/users', [AdminController::class, 'users'], [$auth, $admin]);
    $router->get('/api/v1/admin/orders', [AdminController::class, 'orders'], [$auth, $admin]);
    $router->get('/api/v1/admin/admins', [AdminController::class, 'admins'], [$auth, $superadmin()]);
    $router->post('/api/v1/admin/admins', [AdminController::class, 'admins'], [$csrf, $auth, $superadmin()]);
    $router->delete('/api/v1/admin/admins', [AdminController::class, 'admins'], [$csrf, $auth, $superadmin()]);

    // Payment notify (no CSRF — external)
    $router->post('/api/v1/payments/yoomoney/notify', [PaymentNotifyController::class, 'yoomoney']);
};
