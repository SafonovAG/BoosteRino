<?php
ob_start();
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a><span>/</span><span>Заказ оформлен</span>
        </nav>
    </div>
</section>

<section class="shop-section order-page-section">
    <div class="container">
        <div id="order-success-page" class="order-success-page" data-order-ids="<?= \App\Core\View::e($orderIds ?? '') ?>">
            <p class="muted">Загрузка...</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyClass = 'order-success-pro-page';
$styles = ['/assets/css/order-pages.css'];
$scripts = ['/assets/js/order-success.js'];
include dirname(__DIR__) . '/layouts/main.php';
