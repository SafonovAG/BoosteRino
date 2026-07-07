<?php
ob_start();
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Каталог услуг</h1>
        <p class="muted">Цены указаны за 1000 единиц в рублях. Заказ доступен после <a href="https://boosterino.ru/register">регистрации</a>.</p>
        <div id="services-catalog" class="services-grid">
            <div class="skeleton card"></div>
            <div class="skeleton card"></div>
            <div class="skeleton card"></div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/catalog.js'];
include dirname(__DIR__) . '/layouts/main.php';
