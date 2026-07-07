<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

final class PageController
{
    public static function home(Request $request): void
    {
        Response::html(View::render('public/home', [
            'title' => 'Boosterino - продвижение в соцсетях',
            'page' => 'home',
        ]));
    }

    public static function services(Request $request): void
    {
        Response::html(View::render('public/services', [
            'title' => 'Услуги - Boosterino',
            'page' => 'services',
        ]));
    }

    public static function login(Request $request): void
    {
        if ((new AuthService())->user()) {
            Response::redirect('/cabinet');
            return;
        }
        Response::html(View::render('auth/login', ['title' => 'Вход - Boosterino', 'page' => 'login']));
    }

    public static function register(Request $request): void
    {
        Response::html(View::render('auth/register', ['title' => 'Регистрация - Boosterino', 'page' => 'register']));
    }

    public static function forgotPassword(Request $request): void
    {
        Response::html(View::render('auth/forgot-password', ['title' => 'Восстановление - Boosterino', 'page' => 'forgot']));
    }

    public static function resetPassword(Request $request): void
    {
        Response::html(View::render('auth/reset-password', [
            'title' => 'Новый пароль - Boosterino',
            'page' => 'reset',
            'token' => $request->query('token', ''),
        ]));
    }

    public static function verifyEmail(Request $request): void
    {
        $token = (string) $request->query('token', '');
        $ok = $token !== '' && (new AuthService())->verifyEmail($token);
        Response::html(View::render('auth/verify-email', [
            'title' => 'Подтверждение email - Boosterino',
            'success' => $ok,
        ]));
    }

    public static function cabinet(Request $request): void
    {
        $user = (new AuthService())->user();
        if (!$user) {
            Response::redirect('/login');
            return;
        }
        Response::html(View::render('cabinet/index', [
            'title' => 'Личный кабинет - Boosterino',
            'page' => 'cabinet',
            'user' => $user,
            'csrf' => Session::csrfToken(),
        ]));
    }

    public static function admin(Request $request): void
    {
        $user = (new AuthService())->user();
        if (!$user || !in_array($user['role'], ['admin', 'superadmin'], true)) {
            Response::redirect('/login');
            return;
        }
        Response::html(View::render('admin/index', [
            'title' => 'Админ-панель - Boosterino',
            'page' => 'admin',
            'user' => $user,
            'csrf' => Session::csrfToken(),
            'isSuperadmin' => $user['role'] === 'superadmin',
        ]));
    }
}
