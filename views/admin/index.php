<?php
ob_start();
$isSuper = !empty($super);
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a><span>/</span><span>Админ-панель</span>
        </nav>

        <div class="account-layout admin-shop-shell">
            <aside class="admin-shop-sidebar">
                <div class="admin-shop-sidebar-header">
                    <h2><?= $isSuper ? '👑 Superadmin' : '🛡️ Admin' ?></h2>
                    <p class="muted">Управление магазином</p>
                </div>
                <nav class="admin-shop-nav cabinet-nav">
                    <button type="button" class="active" data-panel="dashboard"><span class="nav-icon">📊</span> Дашборд</button>
                    <button type="button" data-panel="services"><span class="nav-icon">📦</span> Товары</button>
                    <button type="button" data-panel="orders"><span class="nav-icon">🛒</span> Заказы</button>
                    <button type="button" data-panel="users"><span class="nav-icon">👥</span> Клиенты</button>
                    <?php if ($isSuper): ?>
                    <button type="button" data-panel="settings"><span class="nav-icon">⚙️</span> Настройки</button>
                    <?php endif; ?>
                </nav>
            </aside>

            <div class="admin-shop-content app-content">
                <div id="panel-dashboard" class="panel active"><div id="admin-stats">Загрузка...</div></div>
                <div id="panel-services" class="panel"><div id="admin-services" class="card panel-card"></div></div>
                <div id="panel-orders" class="panel"><div id="admin-orders" class="card panel-card"></div></div>
                <div id="panel-users" class="panel"><div id="admin-users" class="card panel-card"></div></div>
                <?php if ($isSuper): ?>
                <div id="panel-settings" class="panel">
                    <div class="card panel-card">
                        <h2>⚙️ Настройки магазина</h2>
                        <p class="muted">Секреты не отображаются - оставьте поле пустым, чтобы не менять.</p>

                        <div class="settings-block">
                            <h3>💳 ЮMoney - HTTP-уведомления</h3>
                            <p class="muted">URL для настройки кошелька ЮMoney:</p>
                            <div class="notify-url-row">
                                <input type="text" id="yoomoney-notify-url" readonly value="https://boosterino.ru/api/v1/payments/yoomoney/notify">
                                <button type="button" class="btn btn-secondary" id="copy-notify-url">Копировать</button>
                            </div>
                            <p class="muted"><a href="https://yoomoney.ru/docs/wallet/using-api/notification-p2p-incoming" target="_blank" rel="noopener">Документация</a></p>
                        </div>

                        <form id="settings-form" class="form">
                            <div class="grid-2">
                                <label>URL сайта<input name="app_url" type="url"></label>
                                <label>Наценка, %<input name="global_markup_percent" type="number" step="0.1"></label>
                                <label>Ключ поставщика<input name="twiboost_api_key" type="password" placeholder="новый ключ"></label>
                                <label>Кошелёк ЮMoney<input name="yoomoney_wallet"></label>
                                <label>Секрет ЮMoney<input name="yoomoney_secret" type="password" placeholder="новый секрет"></label>
                                <label>SMTP-сервер<input name="mail_host"></label>
                                <label>SMTP-порт<input name="mail_port"></label>
                                <label>SMTP-логин<input name="mail_user"></label>
                                <label>SMTP-пароль<input name="mail_pass" type="password" placeholder="новый пароль"></label>
                                <label>Email отправителя<input name="mail_from"></label>
                                <label>Имя отправителя<input name="mail_from_name"></label>
                            </div>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyAttrs = $isSuper ? 'data-superadmin="1"' : '';
$scripts = ['/assets/js/admin.js'];
include dirname(__DIR__) . '/layouts/main.php';
