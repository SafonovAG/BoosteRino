<?php
ob_start();
?>
<section class="cabinet-pro shop-section shop-section-compact">
    <div class="cabinet-pro-bg" aria-hidden="true"></div>
    <div class="container cabinet-pro-container">
        <nav class="breadcrumbs cabinet-pro-crumb">
            <a href="/">Главная</a><span>/</span><span>Личный кабинет</span>
        </nav>

        <div id="email-warning" class="cabinet-pro-alert hidden">
            <span class="cabinet-pro-alert-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i></span>
            <div>
                <strong>Подтвердите email</strong>
                <p>Перед первым заказом проверьте почту и перейдите по ссылке из письма.</p>
            </div>
        </div>

        <div class="cabinet-pro-shell">
            <aside class="cabinet-pro-aside">
                <div class="cabinet-pro-aside-card">
                    <div class="cabinet-pro-profile">
                        <span class="cabinet-pro-kicker">Личный кабинет</span>
                        <p class="cabinet-pro-email muted" id="user-email">Загрузка...</p>
                    </div>

                    <div class="cabinet-pro-balance">
                        <span class="cabinet-pro-balance-label">Баланс</span>
                        <div class="cabinet-pro-balance-value" id="user-balance">...</div>
                        <button type="button" class="btn btn-primary btn-sm cabinet-pro-balance-btn" data-panel-jump="topup">Пополнить</button>
                    </div>

                    <nav class="cabinet-pro-nav cabinet-nav" aria-label="Разделы кабинета">
                        <button type="button" class="active" data-panel="overview">
                            <span class="cabinet-pro-nav-icon" aria-hidden="true"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
                            <span>Обзор</span>
                        </button>
                        <button type="button" data-panel="orders">
                            <span class="cabinet-pro-nav-icon" aria-hidden="true"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
                            <span>Мои заказы</span>
                        </button>
                        <button type="button" data-panel="topup">
                            <span class="cabinet-pro-nav-icon" aria-hidden="true"><i class="bi bi-plus-circle-fill" aria-hidden="true"></i></span>
                            <span>Пополнение</span>
                        </button>
                        <button type="button" data-panel="history">
                            <span class="cabinet-pro-nav-icon" aria-hidden="true"><i class="bi bi-list-ul" aria-hidden="true"></i></span>
                            <span>Операции</span>
                        </button>
                        <button type="button" id="logout-btn" class="cabinet-pro-nav-logout">
                            <span class="cabinet-pro-nav-icon" aria-hidden="true"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></span>
                            <span>Выйти</span>
                        </button>
                    </nav>

                    <a href="/services" class="cabinet-pro-catalog-link">Перейти в каталог</a>
                </div>
            </aside>

            <div class="cabinet-pro-main app-content">
                <div id="panel-overview" class="panel cabinet-pro-panel active">
                    <header class="cabinet-pro-panel-head">
                        <h1>Обзор</h1>
                        <p class="muted">Статистика аккаунта и быстрые действия</p>
                    </header>
                    <div class="cabinet-pro-quick">
                        <button type="button" class="cabinet-pro-quick-card" data-panel-jump="orders">
                            <span class="cabinet-pro-quick-title">Заказы</span>
                            <span class="muted">История и статусы</span>
                        </button>
                        <button type="button" class="cabinet-pro-quick-card" data-panel-jump="topup">
                            <span class="cabinet-pro-quick-title">Пополнение</span>
                            <span class="muted">Карта, SberPay, МИР или ЮMoney · от 10 ₽</span>
                        </button>
                        <a href="/services" class="cabinet-pro-quick-card cabinet-pro-quick-card--link">
                            <span class="cabinet-pro-quick-title">Каталог</span>
                            <span class="muted">Новый заказ</span>
                        </a>
                    </div>
                    <section class="cabinet-pro-card" id="account-stats-section">
                        <h2>Статистика аккаунта</h2>
                        <div id="account-stats" class="cabinet-pro-stats">
                            <p class="muted">Загрузка...</p>
                        </div>
                    </section>
                </div>

                <div id="panel-orders" class="panel cabinet-pro-panel">
                    <header class="cabinet-pro-panel-head cabinet-pro-panel-head--row">
                        <div>
                            <h1>Мои заказы</h1>
                            <p class="muted">Статус выполнения и детали</p>
                        </div>
                        <a href="/services" class="btn btn-primary btn-sm">Новый заказ</a>
                    </header>
                    <div id="orders-list" class="cabinet-pro-panel-body">Загрузка...</div>
                </div>

                <div id="panel-topup" class="panel cabinet-pro-panel">
                    <header class="cabinet-pro-panel-head">
                        <h1>Пополнение баланса</h1>
                        <p class="muted">Минимум 10 ₽ · банковской картой, SberPay, МИР или кошельком ЮMoney</p>
                    </header>
                    <section class="cabinet-pro-card cabinet-pro-card--accent">
                        <form id="topup-form" class="cabinet-pro-form form">
                            <label>Сумма пополнения, ₽
                                <input type="number" name="amount" min="10" step="1" required value="500">
                            </label>
                            <div class="cabinet-pro-amount-presets">
                                <button type="button" class="cabinet-pro-preset" data-amount="100">100 ₽</button>
                                <button type="button" class="cabinet-pro-preset" data-amount="500">500 ₽</button>
                                <button type="button" class="cabinet-pro-preset" data-amount="1000">1 000 ₽</button>
                                <button type="button" class="cabinet-pro-preset" data-amount="3000">3 000 ₽</button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Перейти к оплате</button>
                        </form>
                    </section>
                </div>

                <div id="panel-history" class="panel cabinet-pro-panel">
                    <header class="cabinet-pro-panel-head">
                        <h1>Операции по счёту</h1>
                        <p class="muted">Пополнения и списания</p>
                    </header>
                    <div id="transactions-list" class="cabinet-pro-panel-body">Загрузка...</div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$bodyClass = 'cabinet-pro-page';
$scripts = ['/assets/js/cabinet.js'];
$styles = ['/assets/css/cabinet-pro.css'];
include dirname(__DIR__) . '/layouts/main.php';
