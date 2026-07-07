<?php
ob_start();
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <span>Корзина</span>
        </nav>
        <h1 class="page-title">Корзина</h1>
    </div>
</section>

<section class="shop-section cart-section">
    <div class="container">
        <div id="cart-page" class="cart-page">
            <p class="muted">Загрузка корзины...</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyClass = 'cart-pro-page';
$styles = ['/assets/css/cart-pro.css'];
$scripts = ['/assets/js/cart-page.js'];
include dirname(__DIR__) . '/layouts/main.php';
