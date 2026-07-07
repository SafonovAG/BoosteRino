<?php
ob_start();
?>
<section class="shop-section auth-checkout-section">
    <div class="container">
        <div class="auth-checkout-form card reveal" style="max-width:440px;margin:0 auto;text-align:center">
            <?php if (!empty($success)): ?>
                <div style="font-size:3rem">✅</div>
                <h1>Email подтверждён</h1>
                <p class="muted">Можно войти и оформить заказ в магазине.</p>
                <a href="/login" class="btn btn-primary btn-block">Войти</a>
            <?php else: ?>
                <div style="font-size:3rem">❌</div>
                <h1>Ошибка</h1>
                <p class="muted">Ссылка недействительна или устарела.</p>
                <a href="/register" class="btn btn-secondary btn-block">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
