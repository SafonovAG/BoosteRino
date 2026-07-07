<?php
ob_start();
?>
<section class="section">
    <div class="container">
        <div id="email-warning" class="card hidden" style="margin-bottom:1rem;border-color:var(--warning)">
            Подтвердите email перед первым заказом. Проверьте почту.
        </div>

        <div class="cabinet-grid">
            <aside class="card cabinet-nav">
                <button type="button" class="active" data-panel="overview">Обзор</button>
                <button type="button" data-panel="order">Новый заказ</button>
                <button type="button" data-panel="orders">Мои заказы</button>
                <button type="button" data-panel="topup">Пополнение</button>
                <button type="button" data-panel="history">История</button>
                <button type="button" id="logout-btn" style="margin-top:1rem;color:var(--danger)">Выйти</button>
            </aside>

            <div>
                <div id="panel-overview" class="panel active">
                    <div class="balance-card">
                        <div class="muted">Ваш баланс</div>
                        <div class="amount" id="user-balance">...</div>
                    </div>
                    <div class="card" style="margin-top:1rem">
                        <h2>Смена пароля</h2>
                        <form id="password-form" class="form">
                            <label>Текущий пароль<input type="password" name="current_password" required autocomplete="current-password"></label>
                            <label>Новый пароль<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
                            <button type="submit" class="btn btn-secondary">Сменить пароль</button>
                        </form>
                    </div>
                </div>

                <div id="panel-order" class="panel">
                    <div class="card">
                        <h2>Новый заказ</h2>
                        <form id="order-form" class="form">
                            <label>Услуга<select name="service_id" id="order-service" required></select></label>
                            <label>Ссылка<input type="url" name="link" required placeholder="https://..."></label>
                            <label>Количество<input type="number" name="quantity" id="order-quantity" min="1" required value="100"></label>
                            <p>К оплате: <strong id="order-price">-</strong></p>
                            <label>Способ оплаты
                                <select name="payment_method">
                                    <option value="balance">Списать с баланса</option>
                                    <option value="yoomoney">Оплатить через ЮMoney</option>
                                </select>
                            </label>
                            <button type="submit" class="btn btn-primary">Оформить</button>
                        </form>
                    </div>
                </div>

                <div id="panel-orders" class="panel">
                    <div class="card"><h2>Мои заказы</h2><div id="orders-list">Загрузка...</div></div>
                </div>

                <div id="panel-topup" class="panel">
                    <div class="card">
                        <h2>Пополнение баланса</h2>
                        <form id="topup-form" class="form">
                            <label>Сумма (&#8381;)<input type="number" name="amount" min="10" step="1" required value="500"></label>
                            <button type="submit" class="btn btn-primary">Перейти к оплате</button>
                        </form>
                    </div>
                </div>

                <div id="panel-history" class="panel">
                    <div class="card"><h2>История операций</h2><div id="transactions-list">Загрузка...</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/cabinet.js'];
include dirname(__DIR__) . '/layouts/main.php';
