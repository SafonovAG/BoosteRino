<?php

declare(strict_types=1);

use App\Controllers\Api\AuthApi;
use App\Controllers\Api\AdminApi;
use App\Controllers\Api\NotifyApi;
use App\Controllers\Api\ServiceApi;
use App\Controllers\Api\UserApi;
use App\Controllers\Web\Pages;
use App\Core\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\VerifiedMiddleware;

return static function (Router $r): void {
    $csrf = CsrfMiddleware::class;
    $auth = AuthMiddleware::class;
    $ver = VerifiedMiddleware::class;

    $r->get('/', [Pages::class, 'home']);
    $r->get('/services/{id}', [Pages::class, 'product']);
    $r->get('/services', [Pages::class, 'services']);
    $r->get('/cart', [Pages::class, 'cart']);
    $r->get('/login', [Pages::class, 'login']);
    $r->get('/register', [Pages::class, 'register']);
    $r->get('/forgot-password', [Pages::class, 'forgot']);
    $r->get('/reset-password', [Pages::class, 'reset']);
    $r->get('/verify-email', [Pages::class, 'verify']);
    $r->get('/orders/success', [Pages::class, 'orderSuccess']);
    $r->get('/orders/{id}', [Pages::class, 'order']);
    $r->get('/cabinet', [Pages::class, 'cabinet']);
    $r->get('/admin', [Pages::class, 'admin']);

    $r->get('/api/v1/services', [ServiceApi::class, 'index']);
    $r->get('/api/v1/services/{id}', [ServiceApi::class, 'show']);

    $r->post('/api/v1/auth/register', [AuthApi::class, 'register'], [$csrf, new RateLimitMiddleware('register')]);
    $r->post('/api/v1/auth/login', [AuthApi::class, 'login'], [$csrf, new RateLimitMiddleware('login')]);
    $r->post('/api/v1/auth/logout', [AuthApi::class, 'logout'], [$csrf, $auth]);
    $r->post('/api/v1/auth/forgot-password', [AuthApi::class, 'forgot'], [$csrf, new RateLimitMiddleware('forgot')]);
    $r->post('/api/v1/auth/reset-password', [AuthApi::class, 'reset'], [$csrf]);
    $r->get('/api/v1/auth/verify-email', [AuthApi::class, 'verifyEmail']);

    $r->get('/api/v1/user/profile', [UserApi::class, 'profile'], [$auth]);
    $r->post('/api/v1/user/change-password', [UserApi::class, 'changePassword'], [$csrf, $auth]);
    $r->get('/api/v1/user/orders', [UserApi::class, 'orders'], [$auth]);
    $r->get('/api/v1/user/orders/batch', [UserApi::class, 'ordersBatch'], [$auth]);
    $r->get('/api/v1/user/orders/{id}', [UserApi::class, 'orderShow'], [$auth]);
    $r->post('/api/v1/user/orders', [UserApi::class, 'createOrder'], [$csrf, $auth, $ver]);
    $r->post('/api/v1/user/orders/{id}/sync', [UserApi::class, 'orderSync'], [$csrf, $auth, $ver]);
    $r->post('/api/v1/user/orders/{id}/refill', [UserApi::class, 'refill'], [$csrf, $auth, $ver]);
    $r->post('/api/v1/user/orders/{id}/cancel', [UserApi::class, 'cancel'], [$csrf, $auth, $ver]);
    $r->post('/api/v1/user/balance/topup', [UserApi::class, 'topup'], [$csrf, $auth, $ver]);
    $r->get('/api/v1/user/transactions', [UserApi::class, 'tx'], [$auth]);

    $r->get('/api/v1/admin/dashboard', [AdminApi::class, 'dashboard'], [$auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/settings', [AdminApi::class, 'settingsGet'], [$auth, new AdminMiddleware(true)]);
    $r->put('/api/v1/admin/settings', [AdminApi::class, 'settingsPut'], [$csrf, $auth, new AdminMiddleware(true)]);
    $r->get('/api/v1/admin/services', [AdminApi::class, 'servicesGet'], [$auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/services/categories', [AdminApi::class, 'serviceCategoriesGet'], [$auth, new AdminMiddleware()]);
    $r->put('/api/v1/admin/services/categories', [AdminApi::class, 'serviceCategoriesPut'], [$csrf, $auth, new AdminMiddleware()]);
    $r->put('/api/v1/admin/services/{id}', [AdminApi::class, 'serviceUpdate'], [$csrf, $auth, new AdminMiddleware()]);
    $r->put('/api/v1/admin/services', [AdminApi::class, 'servicesPut'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/services/sync', [AdminApi::class, 'sync'], [$csrf, $auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/users', [AdminApi::class, 'users'], [$auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/users/{id}', [AdminApi::class, 'userShow'], [$auth, new AdminMiddleware()]);
    $r->put('/api/v1/admin/users/{id}', [AdminApi::class, 'userUpdate'], [$csrf, $auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/orders', [AdminApi::class, 'orders'], [$auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/orders/{id}', [AdminApi::class, 'orderShow'], [$auth, new AdminMiddleware()]);
    $r->put('/api/v1/admin/orders/{id}', [AdminApi::class, 'orderUpdate'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/orders/{id}/sync', [AdminApi::class, 'orderSync'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/orders/{id}/refill', [AdminApi::class, 'orderRefill'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/orders/{id}/cancel', [AdminApi::class, 'orderCancel'], [$csrf, $auth, new AdminMiddleware()]);
    $r->delete('/api/v1/admin/orders/{id}', [AdminApi::class, 'orderDelete'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/orders/sync-all', [AdminApi::class, 'ordersSyncAll'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/diagnostics/run', [AdminApi::class, 'diagnosticsRun'], [$csrf, $auth, new AdminMiddleware()]);
    $r->post('/api/v1/admin/diagnostics/supplier', [AdminApi::class, 'diagnosticsSupplier'], [$csrf, $auth, new AdminMiddleware()]);
    $r->get('/api/v1/admin/diagnostics/probe', [AdminApi::class, 'diagnosticsApiProbe'], [$auth, new AdminMiddleware()]);

    $r->post('/api/v1/payments/yoomoney/notify', [NotifyApi::class, 'yoomoney']);
};
