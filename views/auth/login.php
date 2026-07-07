<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card">
            <h1>Вход</h1>
            <form id="login-form" class="form">
                <label>Email<input type="email" name="email" required autocomplete="email"></label>
                <label>Пароль<input type="password" name="password" required autocomplete="current-password"></label>
                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>
            <p class="auth-links">
                <a href="/forgot-password">Забыли пароль?</a> ·
                <a href="/register">Регистрация</a>
            </p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
