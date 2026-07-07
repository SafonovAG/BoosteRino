<?php
ob_start();
$super = !empty($isSuperadmin);
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
                <?php if ($super): ?>
                <button type="button" data-panel="settings">Настройки</button>
                <?php endif; ?>
            </aside>
            <div>
                <div id="panel-dashboard" class="panel active"><div id="admin-stats">Загрузка...</div></div>
                <div id="panel-services" class="panel"><div id="admin-services" class="card"></div></div>
                <div id="panel-orders" class="panel"><div id="admin-orders" class="card"></div></div>
                <div id="panel-users" class="panel"><div id="admin-users" class="card"></div></div>
                <?php if ($super): ?>
                <div id="panel-settings" class="panel">
                    <div class="card">
                        <h2>Настройки системы</h2>
                        <form id="settings-form" class="form">
                            <div class="grid-2">
                                <label>app_url<input name="app_url" type="url"></label>
                                <label>global_markup_percent<input name="global_markup_percent" type="number" step="0.1"></label>
                                <label>twiboost_api_key<input name="twiboost_api_key" type="password" placeholder="новый ключ"></label>
                                <label>yoomoney_wallet<input name="yoomoney_wallet"></label>
                                <label>yoomoney_secret<input name="yoomoney_secret" type="password"></label>
                                <label>mail_host<input name="mail_host"></label>
                                <label>mail_port<input name="mail_port"></label>
                                <label>mail_user<input name="mail_user"></label>
                                <label>mail_pass<input name="mail_pass" type="password"></label>
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
$bodyAttrs = $super ? 'data-superadmin="1"' : '';
$scripts = ['/assets/js/admin.js'];
include dirname(__DIR__) . '/layouts/main.php';
