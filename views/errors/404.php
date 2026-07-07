<?php
ob_start();
?>
<section class="shop-section auth-checkout-section">
    <div class="container">
        <div class="auth-checkout-form card reveal" style="max-width:440px;margin:0 auto;text-align:center">
            <div style="font-size:4rem">404</div>
            <h1>Страница не найдена</h1>
            <p class="muted">Такой страницы нет в магазине.</p>
            <a href="/" class="btn btn-primary">На главную</a>
            <p class="auth-links"><a href="/services">Каталог</a></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
