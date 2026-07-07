<?php
ob_start();
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a><span>/</span><a href="/cabinet">Кабинет</a><span>/</span><span id="order-breadcrumb-label">Заказ</span>
        </nav>
    </div>
</section>

<section class="shop-section order-page-section">
    <div class="container">
        <div id="order-status-page" class="order-status-page" data-order-id="<?= (int) ($orderId ?? 0) ?>">
            <p class="muted">Загрузка заказа...</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyClass = 'order-status-pro-page';
$styles = ['/assets/css/order-pages.css'];
$scripts = ['/assets/js/order-status.js'];
include dirname(__DIR__) . '/layouts/main.php';
