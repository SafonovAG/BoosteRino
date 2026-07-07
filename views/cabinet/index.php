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
                    <button type="button" data-panel="order"><span class="nav-icon">🛒</span> Новый заказ</button>
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

                <div id="panel-order" class="panel">
                    <div class="card panel-card">
                        <h2>🛒 Оформление заказа</h2>
                        <form id="order-form" class="form">
                            <label class="order-service-label">Товар (услуга)
                                <div class="order-service-row">
                                    <img id="order-service-logo" src="/assets/images/logo/default.svg" alt="" width="36" height="36" class="order-service-logo">
                                    <select name="service_id" id="order-service" required></select>
                                </div>
                            </label>
                            <label>🔗 Ссылка на профиль/пост<input type="url" name="link" required placeholder="https://..."></label>
                            <label><span id="order-qty-label">Сколько получите</span>
                                <input type="number" name="quantity" id="order-quantity" min="1" step="1" required value="100">
                            </label>
                            <p class="muted" id="order-qty-hint"></p>
                            <div class="order-total-box">
                                <span>Итого к оплате</span>
                                <strong id="order-price">-</strong>
                            </div>
                            <label>Способ оплаты
                                <select name="payment_method">
                                    <option value="balance">💰 С баланса</option>
                                    <option value="yoomoney">💳 ЮMoney</option>
                                </select>
                            </label>
                            <button type="submit" class="btn btn-primary btn-block">Оформить заказ</button>
                        </form>
                    </div>
                </div>

                <div id="panel-orders" class="panel">
                    <div class="card panel-card">
                        <h2>📦 История заказов</h2>
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
include dirname(__DIR__) . '/layouts/main.php';
