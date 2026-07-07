<?php
ob_start();
?>
<section class="shop-section auth-checkout-section">
    <div class="container">
        <div class="auth-checkout-wrap">
            <div class="auth-checkout-promo card reveal">
                <h2>Вход в магазин</h2>
                <p class="muted">Управляйте заказами, балансом и историей покупок.</p>
                <ul class="promo-list">
                    <li><img src="/assets/images/logo/telegram.svg" alt="" width="20" height="20"> Telegram, VK, YouTube</li>
                    <li><i class="bi bi-credit-card app-icon app-icon--blue" aria-hidden="true"></i> Оплата ЮMoney</li>
                    <li><i class="bi bi-box-seam app-icon app-icon--violet" aria-hidden="true"></i> Статус каждого заказа</li>
                </ul>
            </div>
            <div class="auth-checkout-form card reveal">
                <h1>Вход</h1>
                <form id="login-form" class="form">
                    <label>Email<input type="email" name="email" required autocomplete="email"></label>
                    <label>Пароль<input type="password" name="password" required autocomplete="current-password"></label>
                    <button type="submit" class="btn btn-primary btn-block">Войти</button>
                </form>
                <p class="auth-links"><a href="/forgot-password">Забыли пароль?</a> · <a href="/register">Регистрация</a></p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
