<?php
ob_start();
?>
<section class="section">
    <div class="container">
        <div id="email-warning" class="card alert-card hidden">
            <span class="alert-icon">⚠️</span>
            <div>
                <strong>Подтвердите email</strong>
                <p class="muted" style="margin:0.25rem 0 0">Перед первым заказом проверьте почту и перейдите по ссылке из письма.</p>
            </div>
        </div>

        <div class="page-header reveal">
            <h1 class="page-title">💎 Личный кабинет</h1>
            <p class="muted">Управление заказами, балансом и настройками аккаунта</p>
        </div>

        <div class="app-shell">
            <aside class="card app-sidebar">
                <div class="app-sidebar-header">
                    <h2>Меню</h2>
                    <p class="muted">Ваш аккаунт</p>
                </div>
                <nav class="cabinet-nav">
                    <button type="button" class="active" data-panel="overview"><span class="nav-icon">📊</span> Обзор</button>
                    <button type="button" data-panel="order"><span class="nav-icon">🛒</span> Новый заказ</button>
                    <button type="button" data-panel="orders"><span class="nav-icon">📋</span> Мои заказы</button>
                    <button type="button" data-panel="topup"><span class="nav-icon">💰</span> Пополнение</button>
                    <button type="button" data-panel="history"><span class="nav-icon">📜</span> История</button>
                    <button type="button" id="logout-btn" class="nav-logout"><span class="nav-icon">🚪</span> Выйти</button>
                </nav>
            </aside>

            <div class="app-content">
                <div id="panel-overview" class="panel active">
                    <div class="balance-card reveal">
                        <div class="muted">Ваш баланс</div>
                        <div class="amount" id="user-balance">...</div>
                    </div>
                    <div class="card panel-card reveal" style="margin-top:1.25rem">
                        <h2><span class="panel-icon">🔐</span> Смена пароля</h2>
                        <form id="password-form" class="form">
                            <label>Текущий пароль<input type="password" name="current_password" required autocomplete="current-password"></label>
                            <label>Новый пароль<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
                            <button type="submit" class="btn btn-secondary">💾 Сменить пароль</button>
                        </form>
                    </div>
                </div>

                <div id="panel-order" class="panel">
                    <div class="card panel-card">
                        <h2><span class="panel-icon">🛒</span> Новый заказ</h2>
                        <form id="order-form" class="form">
                            <label>Услуга<select name="service_id" id="order-service" required></select></label>
                            <label>🔗 Ссылка<input type="url" name="link" required placeholder="https://..."></label>
                            <label>Количество<input type="number" name="quantity" id="order-quantity" min="1" required value="100"></label>
                            <p class="muted">К оплате: <strong id="order-price" style="font-size:1.25rem;color:var(--accent)">-</strong></p>
                            <label>Способ оплаты
                                <select name="payment_method">
                                    <option value="balance">💰 Списать с баланса</option>
                                    <option value="yoomoney">💳 Оплатить через ЮMoney</option>
                                </select>
                            </label>
                            <button type="submit" class="btn btn-primary">⚡ Оформить заказ</button>
                        </form>
                    </div>
                </div>

                <div id="panel-orders" class="panel">
                    <div class="card panel-card">
                        <h2><span class="panel-icon">📋</span> Мои заказы</h2>
                        <div id="orders-list">Загрузка...</div>
                    </div>
                </div>

                <div id="panel-topup" class="panel">
                    <div class="card panel-card">
                        <h2><span class="panel-icon">💰</span> Пополнение баланса</h2>
                        <form id="topup-form" class="form">
                            <label>Сумма (₽)<input type="number" name="amount" min="10" step="1" required value="500"></label>
                            <button type="submit" class="btn btn-primary">💳 Перейти к оплате</button>
                        </form>
                    </div>
                </div>

                <div id="panel-history" class="panel">
                    <div class="card panel-card">
                        <h2><span class="panel-icon">📜</span> История операций</h2>
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
