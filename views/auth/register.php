<?php
ob_start();
?>
<section class="shop-section auth-checkout-section">
    <div class="container">
        <div class="auth-checkout-wrap">
            <div class="auth-checkout-promo card reveal">
                <h2>Регистрация покупателя</h2>
                <p class="muted">Создайте аккаунт и получите доступ к полному каталогу SMM-услуг.</p>
                <div class="platforms-row platforms-row-compact">
                    <img src="/assets/images/logo/telegram.svg" alt="Telegram" width="32" height="32">
                    <img src="/assets/images/logo/vk.svg" alt="VK" width="32" height="32">
                    <img src="/assets/images/logo/youtube.svg" alt="YouTube" width="32" height="32">
                    <img src="/assets/images/logo/tiktok.svg" alt="TikTok" width="32" height="32">
                </div>
            </div>
            <div class="auth-checkout-form card reveal">
                <h1>Регистрация</h1>
                <form id="register-form" class="form">
                    <label>Email<input type="email" name="email" required autocomplete="email"></label>
                    <label>Пароль (мин. 8 символов)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                    <button type="submit" class="btn btn-primary btn-block">Создать аккаунт</button>
                </form>
                <p class="auth-links"><a href="/login">Уже есть аккаунт? Войти</a></p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
