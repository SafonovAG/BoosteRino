<!DOCTYPE html>
<html lang="ru" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    (function () {
      var pref = localStorage.getItem('theme') || 'auto';
      var dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      var resolved = pref === 'auto' ? (dark ? 'dark' : 'light') : pref;
      document.documentElement.setAttribute('data-theme-pref', pref);
      document.documentElement.setAttribute('data-theme', resolved);
      document.documentElement.style.colorScheme = resolved;
    })();
    </script>
    <title><?= \App\Core\View::e($title ?? 'Boosterino - магазин SMM-услуг') ?></title>
    <meta name="description" content="Boosterino - интернет-магазин накрутки и продвижения в соцсетях. Telegram, VK, YouTube, TikTok. Оплата в рублях.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/icons.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/shop.css">
    <link rel="stylesheet" href="/assets/css/shop-fixes.css">
    <link rel="stylesheet" href="/assets/css/select-pro.css">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= \App\Core\View::e($style) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <meta name="csrf-token" content="<?= \App\Core\View::e(\App\Core\Session::csrf()) ?>">
</head>
<body class="shop-page<?= !empty($bodyClass) ? ' ' . \App\Core\View::e($bodyClass) : '' ?>" <?= $bodyAttrs ?? '' ?>>
<?php
$authUser = null;
try {
    $authUser = (new \App\Services\AuthService())->user();
} catch (\Throwable) {
}
$platforms = \App\Services\ServiceLogo::platforms();
?>
    <div class="shop-topbar" id="shop-topbar">
        <div class="shop-topbar-inner">
            <span><i class="bi bi-gift-fill app-icon app-icon--amber" aria-hidden="true"></i> Выгодные цены - оплата в рублях, мгновенный запуск заказов</span>
            <button type="button" class="shop-topbar-close" id="topbar-close" aria-label="Закрыть"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
    </div>

    <header class="store-header">
        <div class="container store-header-inner">
            <a href="/" class="store-logo">
                <img src="/assets/images/logo/default.svg" alt="" width="36" height="36">
                <span>Booste<strong>Rino</strong></span>
            </a>

            <nav class="store-nav" id="store-nav">
                <a href="/" class="<?= ($page ?? '') === 'home' ? 'active' : '' ?>"><i class="bi bi-house-door app-icon app-icon--inline" aria-hidden="true"></i> Главная</a>
                <a href="/services" class="<?= ($page ?? '') === 'services' ? 'active' : '' ?>"><i class="bi bi-grid-3x3-gap app-icon app-icon--inline" aria-hidden="true"></i> Каталог</a>
                <a href="/cart" class="<?= ($page ?? '') === 'cart' ? 'active' : '' ?>"><i class="bi bi-cart3 app-icon app-icon--inline" aria-hidden="true"></i> Корзина</a>
                <?php if ($authUser): ?>
                    <a href="/cabinet" class="<?= ($page ?? '') === 'cabinet' ? 'active' : '' ?>"><i class="bi bi-person-circle app-icon app-icon--inline" aria-hidden="true"></i> Мой кабинет</a>
                    <?php if (in_array($authUser['role'], ['admin', 'superadmin'], true)): ?>
                        <a href="/admin" class="<?= ($page ?? '') === 'admin' ? 'active' : '' ?>">Админ</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="store-header-actions">
                <a href="/cart" class="cart-pill" id="cart-link" title="Корзина">
                    <span class="cart-pill-icon"><i class="bi bi-cart3 app-icon app-icon--accent" aria-hidden="true"></i></span>
                    <span class="cart-pill-count" id="cart-count">0</span>
                </a>
                <?php if ($authUser): ?>
                    <a href="/cabinet" class="balance-pill">
                        <span class="balance-pill-label">Баланс</span>
                        <span class="balance-pill-value"><?= number_format((float) $authUser['balance_rub'], 0, '.', ' ') ?> ₽</span>
                    </a>
                <?php else: ?>
                    <a href="/login" class="btn btn-ghost">Вход</a>
                    <a href="/register" class="btn btn-primary">Регистрация</a>
                <?php endif; ?>
                <button type="button" class="btn-icon" id="theme-toggle" aria-label="Тема"><i class="bi bi-circle-half app-icon" aria-hidden="true"></i></button>
                <button type="button" class="btn-icon store-nav-toggle" id="nav-toggle" aria-label="Меню"><i class="bi bi-list app-icon" aria-hidden="true"></i></button>
            </div>
        </div>
    </header>

    <main class="shop-main">
        <?= $content ?? '' ?>
    </main>

    <footer class="store-footer">
        <div class="container">
            <div class="store-footer-grid">
                <div class="store-footer-col">
                    <a href="/" class="store-logo store-footer-logo">
                        <img src="/assets/images/logo/default.svg" alt="" width="32" height="32">
                        <span>Booste<strong>Rino</strong></span>
                    </a>
                    <p class="store-footer-desc">Интернет-магазин SMM-услуг: подписчики, лайки, просмотры и активность для популярных соцсетей.</p>
                    <div class="platforms-row platforms-row-compact">
                        <?php foreach (array_slice($platforms, 1) as $p): ?>
                            <span class="platform-chip" title="<?= \App\Core\View::e($p['name']) ?>">
                                <img src="<?= \App\Core\View::e($p['logo']) ?>" alt="<?= \App\Core\View::e($p['name']) ?>" width="28" height="28">
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="store-footer-col">
                    <h4>Магазин</h4>
                    <ul>
                        <li><a href="/services">Каталог услуг</a></li>
                        <li><a href="/cart">Корзина</a></li>
                        <li><a href="/register">Регистрация</a></li>
                        <li><a href="/cabinet">Личный кабинет</a></li>
                    </ul>
                </div>
                <div class="store-footer-col">
                    <h4>Платформы</h4>
                    <ul>
                        <li><a href="/services?platform=telegram">Telegram</a></li>
                        <li><a href="/services?platform=vk">VK</a></li>
                        <li><a href="/services?platform=youtube">YouTube</a></li>
                        <li><a href="/services?platform=tiktok">TikTok</a></li>
                    </ul>
                </div>
                <div class="store-footer-col">
                    <h4>Оплата</h4>
                    <ul>
                        <li>Карта, SberPay, МИР или кошелёк ЮMoney</li>
                        <li>Предоплата на баланс</li>
                        <li>Оплата при заказе</li>
                    </ul>
                </div>
            </div>
            <div class="store-footer-bottom">
                <p>&copy; <?= date('Y') ?> <a href="https://boosterino.ru">boosterino.ru</a> - все цены в рублях</p>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
    <script src="/assets/js/icons.js"></script>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/ui.js"></script>
    <script src="/assets/js/qty-pack.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/cart-fly.js"></script>
    <script src="/assets/js/link-validator.js"></script>
    <script src="/assets/js/product-cards.js"></script>
    <script>
    document.getElementById('topbar-close')?.addEventListener('click', () => {
        document.getElementById('shop-topbar')?.remove();
    });
    document.getElementById('nav-toggle')?.addEventListener('click', () => {
        document.getElementById('store-nav')?.classList.toggle('open');
    });
    </script>
    <?php if (!empty($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= \App\Core\View::e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
