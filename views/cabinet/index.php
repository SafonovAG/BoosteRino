<?php
ob_start();
?>
<section class="shop-section shop-section-compact">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="/">Главная</a><span>/</span><span>Личный кабинет</span>
        </nav>

        <div id="email-warning" class="alert-banner hidden">
            <span>⚠️</span>
            <div><strong>Подтвердите email</strong> перед первым заказом. Проверьте почту.</div>
        </div>

        <div class="account-layout admin-shop-shell">
            <aside class="admin-shop-sidebar">
                <div class="admin-shop-sidebar-header">
                    <h2>🛍️ Мой кабинет</h2>
                    <p class="muted">Управление заказами</p>
                </div>
                <nav class="admin-shop-nav cabinet-nav">
                    <button type="button" class="active" data-panel="overview"><span class="nav-icon">📊</span> Обзор</button>
                    <button type="button" data-panel="orders"><span class="nav-icon">📦</span> Мои заказы</button>
                    <button type="button" data-panel="topup"><span class="nav-icon">💰</span> Пополнение</button>
                    <button type="button" data-panel="history"><span class="nav-icon">📜</span> История</button>
                    <button type="button" id="logout-btn" class="nav-logout"><span class="nav-icon">🚪</span> Выйти</button>
                </nav>
            </aside>

            <div class="admin-shop-content app-content">
                <div id="panel-overview" class="panel active">
                    <div class="balance-card shop-balance-card">
                        <div class="muted">Баланс счёта</div>
                        <div class="amount" id="user-balance">...</div>
                        <a href="#" data-panel-jump="topup" class="btn btn-secondary btn-sm balance-topup-link">Пополнить</a>
                    </div>
                    <div class="card panel-card" style="margin-top:1.25rem">
                        <h2>🔐 Смена пароля</h2>
                        <form id="password-form" class="form">
                            <label>Текущий пароль<input type="password" name="current_password" required autocomplete="current-password"></label>
                            <label>Новый пароль<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
                            <button type="submit" class="btn btn-secondary">Сохранить</button>
                        </form>
                    </div>
                </div>

                <div id="panel-orders" class="panel">
                    <div class="card panel-card cabinet-orders-panel">
                        <div class="cabinet-orders-head">
                            <h2>📦 История заказов</h2>
                            <a href="/services" class="btn btn-primary btn-sm">Каталог</a>
                        </div>
                        <div id="orders-list">Загрузка...</div>
                    </div>
                </div>

                <div id="panel-topup" class="panel">
                    <div class="card panel-card">
                        <h2>💰 Пополнение баланса</h2>
                        <p class="muted">Минимум 10 ₽. Оплата через ЮMoney.</p>
                        <form id="topup-form" class="form">
                            <label>Сумма (₽)<input type="number" name="amount" min="10" step="1" required value="500"></label>
                            <button type="submit" class="btn btn-primary btn-block">Перейти к оплате</button>
                        </form>
                    </div>
                </div>

                <div id="panel-history" class="panel">
                    <div class="card panel-card">
                        <h2>📜 Операции по счёту</h2>
                        <div id="transactions-list">Загрузка...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/cabinet.js'];
$styles = ['/assets/css/cabinet-orders.css'];
include dirname(__DIR__) . '/layouts/main.php';
