<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\ServiceCatalog;

final class Pages
{
    public static function home(Request $r): void
    {
        Response::html(View::render('public/home', ['title' => 'Boosterino - SMM продвижение', 'page' => 'home']));
    }

    public static function services(Request $r): void
    {
        Response::html(View::render('public/services', ['title' => 'Услуги - Boosterino', 'page' => 'services']));
    }

    public static function product(Request $r, array $par): void
    {
        $id = (int) ($par['id'] ?? 0);
        $s = ServiceCatalog::find($id);
        if (!$s || !$s['is_active']) {
            Response::html(View::render('errors/404', ['title' => '404 - Boosterino']), 404);
            return;
        }
        Response::html(View::render('public/product', [
            'title' => $s['name'] . ' - Boosterino',
            'page' => 'services',
            'serviceId' => $id,
        ]));
    }

    public static function cart(Request $r): void
    {
        Response::html(View::render('public/cart', ['title' => 'Корзина - Boosterino', 'page' => 'cart']));
    }

    public static function login(Request $r): void
    {
        if ((new AuthService())->user()) {
            Response::redirect('/cabinet');
        }
        Response::html(View::render('auth/login', ['title' => 'Вход - Boosterino', 'page' => 'login']));
    }

    public static function register(Request $r): void
    {
        Response::html(View::render('auth/register', ['title' => 'Регистрация - Boosterino', 'page' => 'register']));
    }

    public static function forgot(Request $r): void
    {
        Response::html(View::render('auth/forgot', ['title' => 'Восстановление - Boosterino']));
    }

    public static function reset(Request $r): void
    {
        Response::html(View::render('auth/reset', ['title' => 'Новый пароль - Boosterino', 'token' => $r->query('token', '')]));
    }

    public static function verify(Request $r): void
    {
        $ok = (new AuthService())->verifyEmail((string) $r->query('token', ''));
        Response::html(View::render('auth/verify', ['title' => 'Email - Boosterino', 'success' => $ok]));
    }

    public static function cabinet(Request $r): void
    {
        if (!(new AuthService())->user()) {
            Response::redirect('/login');
        }
        Response::html(View::render('cabinet/index', ['title' => 'Кабинет - Boosterino', 'page' => 'cabinet', 'csrf' => Session::csrf()]));
    }

    public static function orderSuccess(Request $r): void
    {
        if (!(new AuthService())->user()) {
            Response::redirect('/login?next=' . rawurlencode('/orders/success' . ($r->query('ids') ? '?ids=' . $r->query('ids') : '')));
        }
        Response::html(View::render('public/order-success', [
            'title' => 'Заказ оформлен - Boosterino',
            'page' => 'cabinet',
            'orderIds' => (string) $r->query('ids', ''),
        ]));
    }

    public static function order(Request $r, array $par): void
    {
        if (!(new AuthService())->user()) {
            Response::redirect('/login?next=' . rawurlencode('/orders/' . ($par['id'] ?? '')));
        }
        $id = (int) ($par['id'] ?? 0);
        if ($id <= 0) {
            Response::html(View::render('errors/404', ['title' => '404 - Boosterino']), 404);
            return;
        }
        Response::html(View::render('public/order', [
            'title' => 'Заказ #' . $id . ' - Boosterino',
            'page' => 'cabinet',
            'orderId' => $id,
        ]));
    }

    public static function admin(Request $r): void
    {
        $u = (new AuthService())->user();
        if (!$u || !in_array($u['role'], ['admin', 'superadmin'], true)) {
            Response::redirect('/login');
        }
        Response::html(View::render('admin/index', [
            'title' => 'Админ - Boosterino',
            'page' => 'admin',
            'csrf' => Session::csrf(),
            'super' => $u['role'] === 'superadmin',
        ]));
    }
}
