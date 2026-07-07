<?php
ob_start();
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <a href="/services">Каталог</a>
            <span>/</span>
            <span id="product-breadcrumb">Товар</span>
        </nav>
    </div>
</section>

<section class="shop-section product-section">
    <div class="container">
        <div id="product-page" class="product-page" data-service-id="<?= (int) ($serviceId ?? 0) ?>">
            <div class="product-page-loading card">
                <p class="muted">Загрузка товара...</p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/product.js'];
include dirname(__DIR__) . '/layouts/main.php';
