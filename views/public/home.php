<?php
ob_start();
?>
<section class="shop-hero shop-section shop-hero-centered">
    <div class="container shop-hero-grid">
        <div class="shop-hero-content reveal">
            <span class="shop-hero-badge"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Быстрое продвижение в соцсетях</span>
            <h1 class="shop-hero-title">
                Быстрая накрутка<br>
                <span class="gradient-text">в социальных сетях</span>
            </h1>
            <p class="shop-hero-lead">Увеличьте подписчиков, просмотры и реакции по выгодным ценам. Прозрачный каталог, личный кабинет и оплата в рублях банковской картой, SberPay, МИР или кошельком ЮMoney.</p>
            <div class="shop-hero-actions">
                <a href="/services" class="btn btn-primary btn-lg"><i class="bi bi-grid-3x3-gap-fill app-icon app-icon--inline" aria-hidden="true"></i> Открыть каталог</a>
                <a href="/register" class="btn btn-secondary btn-lg">Создать аккаунт</a>
            </div>
            <div class="shop-hero-perks">
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span> Цены за 1000 ед.</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span> Рефилл</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span> Статус в кабинете</span>
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
            <a href="/services" class="btn btn-secondary">Весь каталог <i class="bi bi-arrow-right app-icon app-icon--inline" aria-hidden="true"></i></a>
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
