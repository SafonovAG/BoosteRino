<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="auth-wrap">
            <div class="card card-premium auth-promo reveal">
                <span class="badge">🔐 Безопасный вход</span>
                <h2>Добро пожаловать в Boosterino</h2>
                <p class="muted">Войдите в личный кабинет для управления заказами, балансом и настройками аккаунта.</p>
                <ul class="promo-list">
                    <li><span>📊</span> История всех заказов</li>
                    <li><span>💰</span> Пополнение баланса онлайн</li>
                    <li><span>⚡</span> Быстрое оформление услуг</li>
                </ul>
            </div>
            <div class="card auth-card reveal">
                <h1>👋 Вход</h1>
                <form id="login-form" class="form">
                    <label>📧 Email<input type="email" name="email" required autocomplete="email"></label>
                    <label>🔑 Пароль<input type="password" name="password" required autocomplete="current-password"></label>
                    <button type="submit" class="btn btn-primary btn-block">Войти в кабинет</button>
                </form>
                <p class="auth-links">
                    <a href="/forgot-password">Забыли пароль?</a> &middot;
                    <a href="/register">Регистрация</a>
                </p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
