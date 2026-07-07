<?php
ob_start();
?>
<section class="shop-section auth-checkout-section">
    <div class="container">
        <div class="auth-checkout-form card reveal" style="max-width:440px;margin:0 auto">
            <h1>Восстановление пароля</h1>
            <form id="forgot-form" class="form">
                <label>Email<input type="email" name="email" required></label>
                <button type="submit" class="btn btn-primary btn-block">Отправить ссылку</button>
            </form>
            <p class="auth-links"><a href="/login">Вернуться ко входу</a></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
