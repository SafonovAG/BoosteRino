<?php
use App\Services\ServiceLogo;
$platforms = ServiceLogo::platforms();
$activePlatform = $_GET['platform'] ?? 'all';
ob_start();
?>
<section class="shop-section shop-section-compact catalog-hero">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>/</span>
            <span>Каталог</span>
        </nav>
        <h1 class="page-title">Каталог SMM-услуг</h1>
        <p class="page-lead">Выберите платформу и услугу. Все цены указаны за 1000 единиц в рублях.</p>
    </div>
</section>

<section class="shop-section catalog-section">
    <div class="container catalog-layout">
        <aside class="filter-sidebar" id="filter-sidebar">
            <div class="filter-sidebar-header">
                <h3>Фильтры</h3>
                <button type="button" class="filter-close" id="filter-close" aria-label="Закрыть">×</button>
            </div>

            <div class="filter-block">
                <h4>Платформа</h4>
                <ul class="filter-list" id="platform-filters">
                    <?php foreach ($platforms as $p): ?>
                        <li>
                            <button type="button"
                                class="filter-item <?= $activePlatform === $p['slug'] ? 'active' : '' ?>"
                                data-platform="<?= \App\Core\View::e($p['slug']) ?>">
                                <img src="<?= \App\Core\View::e($p['logo']) ?>" alt="" width="24" height="24">
                                <span><?= \App\Core\View::e($p['name']) ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="filter-block">
                <h4>Поиск</h4>
                <input type="search" id="catalog-search" class="filter-search" placeholder="Название услуги...">
            </div>

            <?php
            $catalogUser = null;
            try { $catalogUser = (new \App\Services\AuthService())->user(); } catch (\Throwable) {}
            if (!$catalogUser):
            ?>
                <div class="filter-promo card">
                    <p><strong>Нужен аккаунт</strong> для оформления заказа</p>
                    <a href="/register" class="btn btn-primary btn-block btn-sm">Регистрация</a>
                </div>
            <?php endif; ?>
        </aside>

        <div class="catalog-main">
            <div class="catalog-toolbar">
                <button type="button" class="btn btn-secondary btn-sm" id="filter-open">☰ Фильтры</button>
                <span id="catalog-count" class="catalog-count">Загрузка...</span>
            </div>
            <div id="services-catalog" class="product-grid">
                <div class="product-card skeleton"></div>
                <div class="product-card skeleton"></div>
                <div class="product-card skeleton"></div>
                <div class="product-card skeleton"></div>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/catalog.js'];
include dirname(__DIR__) . '/layouts/main.php';
