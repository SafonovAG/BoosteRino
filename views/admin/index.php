<?php
ob_start();
$isSuper = !empty($super);
?>
<section class="section">
    <div class="container">
        <h1 class="page-title">Админ-панель</h1>
        <div class="cabinet-grid">
            <aside class="card cabinet-nav">
                <button type="button" class="active" data-panel="dashboard">Дашборд</button>
                <button type="button" data-panel="services">Услуги</button>
                <button type="button" data-panel="orders">Заказы</button>
                <button type="button" data-panel="users">Пользователи</button>
                <?php if ($isSuper): ?>
                <button type="button" data-panel="settings">Настройки</button>
                <?php endif; ?>
            </aside>
            <div>
                <div id="panel-dashboard" class="panel active"><div id="admin-stats">Загрузка...</div></div>
                <div id="panel-services" class="panel"><div id="admin-services" class="card"></div></div>
                <div id="panel-orders" class="panel"><div id="admin-orders" class="card"></div></div>
                <div id="panel-users" class="panel"><div id="admin-users" class="card"></div></div>
                <?php if ($isSuper): ?>
                <div id="panel-settings" class="panel">
                    <div class="card">
                        <h2>Настройки системы</h2>
                        <p class="muted">Доступно только superadmin. Секреты не отображаются - оставьте поле пустым, чтобы не менять.</p>
                        <form id="settings-form" class="form">
                            <div class="grid-2">
                                <label>app_url<input name="app_url" type="url"></label>
                                <label>global_markup_percent<input name="global_markup_percent" type="number" step="0.1"></label>
                                <label>twiboost_api_key<input name="twiboost_api_key" type="password" placeholder="новый ключ"></label>
                                <label>yoomoney_wallet<input name="yoomoney_wallet"></label>
                                <label>yoomoney_secret<input name="yoomoney_secret" type="password" placeholder="новый secret"></label>
                                <label>mail_host<input name="mail_host"></label>
                                <label>mail_port<input name="mail_port"></label>
                                <label>mail_user<input name="mail_user"></label>
                                <label>mail_pass<input name="mail_pass" type="password" placeholder="новый пароль"></label>
                                <label>mail_from<input name="mail_from"></label>
                                <label>mail_from_name<input name="mail_from_name"></label>
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
