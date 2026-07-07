<!DOCTYPE html>
<html lang="ru" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\View::e($title ?? 'Boosterino') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <meta name="csrf-token" content="<?= \App\Core\View::e(\App\Core\Session::csrf()) ?>">
</head>
<body <?= $bodyAttrs ?? '' ?>>
    <div class="bg-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
    <?php
    $authUser = null;
    try {
        $authUser = (new \App\Services\AuthService())->user();
    } catch (\Throwable) {
    }
    ?>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="logo">
                <span class="logo-icon" aria-hidden="true">🚀</span>
                Booste<span>Rino</span>
            </a>
            <nav class="nav" id="main-nav">
                <a href="/services" class="<?= ($page ?? '') === 'services' ? 'active' : '' ?>">Услуги</a>
                <?php if ($authUser): ?>
                    <a href="/cabinet" class="<?= ($page ?? '') === 'cabinet' ? 'active' : '' ?>">Кабинет</a>
                    <?php if (in_array($authUser['role'], ['admin', 'superadmin'], true)): ?>
                        <a href="/admin" class="<?= ($page ?? '') === 'admin' ? 'active' : '' ?>">Админ</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="/login" class="<?= ($page ?? '') === 'login' ? 'active' : '' ?>">Вход</a>
                    <a href="/register">Регистрация</a>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <button type="button" class="btn-icon" id="theme-toggle" aria-label="Переключить тему">🌓</button>
                <button type="button" class="btn-icon nav-toggle" id="nav-toggle" aria-label="Меню">☰</button>
            </div>
        </div>
    </header>

    <main class="main">
        <?= $content ?? '' ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p class="footer-brand">🚀 <a href="https://boosterino.ru">Boosterino</a></p>
            <p class="muted">&copy; <?= date('Y') ?> - премиальное SMM-продвижение в соцсетях</p>
            <div class="trust-row">
                <span class="trust-item"><span>⚡</span> Мгновенный старт</span>
                <span class="trust-item"><span>🔒</span> Безопасная оплата</span>
                <span class="trust-item"><span>💎</span> Премиум качество</span>
            </div>
        </div>
    </footer>

    <div id="toast-container" class="toast-container"></div>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/ui.js"></script>
    <?php if (!empty($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= \App\Core\View::e($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
