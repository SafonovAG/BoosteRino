<?php
ob_start();
?>
<section class="shop-hero shop-section shop-hero-centered">
    <div class="container shop-hero-grid">
        <div class="shop-hero-content reveal">
            <span class="shop-hero-badge">⚡ Быстрое продвижение в соцсетях</span>
            <h1 class="shop-hero-title">
                Быстрая накрутка<br>
                <span class="gradient-text">в социальных сетях</span>
            </h1>
            <p class="shop-hero-lead">Увеличьте подписчиков, просмотры и реакции по выгодным ценам. Прозрачный каталог, личный кабинет и оплата в рублях через ЮMoney.</p>
            <div class="shop-hero-actions">
                <a href="/services" class="btn btn-primary btn-lg">🛒 Открыть каталог</a>
                <a href="/register" class="btn btn-secondary btn-lg">Создать аккаунт</a>
            </div>
            <div class="shop-hero-perks">
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Цены за 1000 ед.</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Рефилл</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Статус в кабинете</span>
            </div>
        </div>
    </div>
</section>

<section class="shop-section home-featured-section" id="featured">
    <div class="container">
        <div class="home-featured-head reveal">
            <div>
                <h2 class="section-title">Популярные товары</h2>
                <p class="section-subtitle">Лучшие предложения по цене - добавьте в корзину в один клик</p>
            </div>
            <a href="/services" class="btn btn-secondary">Весь каталог →</a>
        </div>
        <div id="featured-products" class="home-tiles-grid">
            <div class="home-tile home-tile--skeleton"></div>
            <div class="home-tile home-tile--skeleton"></div>
            <div class="home-tile home-tile--skeleton"></div>
            <div class="home-tile home-tile--skeleton"></div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyClass = 'home-pro-page';
$styles = ['/assets/css/home-pro.css'];
$scripts = ['/assets/js/shop-home.js'];
include dirname(__DIR__) . '/layouts/main.php';
