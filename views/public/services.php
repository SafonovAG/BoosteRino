<?php
use App\Services\ServiceLogo;
$platforms = ServiceLogo::platforms();
$activePlatform = $_GET['platform'] ?? 'all';
$bodyClass = 'catalog-pro-page';
$styles = ['/assets/css/catalog-pro.css'];
ob_start();
?>
<div class="catalog-pro">
    <div class="catalog-pro-deck" id="catalog-deck">
        <div class="container catalog-pro-deck-inner">
            <div class="catalog-pro-top">
                <div class="catalog-pro-heading">
                    <h1 class="catalog-pro-title">Каталог</h1>
                    <span class="catalog-pro-count" id="catalog-count">...</span>
                </div>
                <div class="catalog-pro-search">
                    <svg class="catalog-pro-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" id="catalog-search" placeholder="Поиск услуги..." autocomplete="off">
                    <button type="button" class="catalog-pro-search-clear hidden" id="search-clear" aria-label="Очистить">×</button>
                </div>
            </div>

            <div class="catalog-pro-rail-wrap">
                <div class="catalog-pro-rail" id="platform-filters" role="tablist">
                    <?php foreach ($platforms as $p): ?>
                        <button type="button"
                            class="catalog-pro-chip <?= $activePlatform === $p['slug'] ? 'is-active' : '' ?>"
                            data-platform="<?= \App\Core\View::e($p['slug']) ?>"
                            role="tab"
                            aria-selected="<?= $activePlatform === $p['slug'] ? 'true' : 'false' ?>">
                            <span class="catalog-pro-chip-icon">
                                <img src="<?= \App\Core\View::e($p['logo']) ?>" alt="" width="22" height="22">
                            </span>
                            <span class="catalog-pro-chip-label"><?= \App\Core\View::e($p['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="catalog-pro-cats-wrap hidden" id="category-rail-wrap">
                <div class="catalog-pro-cats" id="category-filters"></div>
            </div>

            <div class="catalog-pro-active" id="active-filters"></div>
        </div>
    </div>

    <div class="container catalog-pro-body">
        <div id="services-catalog" class="catalog-pro-list is-loading">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="catalog-row catalog-row-skeleton"></div>
            <?php endfor; ?>
        </div>
        <nav id="catalog-pagination" class="catalog-pro-pagination" aria-label="Страницы"></nav>
    </div>

    <button type="button" class="catalog-pro-fab" id="scroll-top" aria-label="Наверх" hidden>↑</button>
</div>

<div class="catalog-pro-overlay" id="catalog-overlay" hidden></div>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/catalog.js'];
include dirname(__DIR__) . '/layouts/main.php';
