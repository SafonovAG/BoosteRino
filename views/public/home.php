<?php
ob_start();
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content animate-in">
            <span class="badge badge-glow">✨ Премиальный SMM-сервис</span>
            <h1>Продвижение в соцсетях - быстро, красиво, прозрачно</h1>
            <p class="lead">Подписчики, лайки, просмотры и активность для Instagram, Telegram, VK и других платформ. Оплата в рублях, личный кабинет, мгновенный старт заказов.</p>
            <div class="hero-actions">
                <a href="/register" class="btn btn-primary">🚀 Начать бесплатно</a>
                <a href="/services" class="btn btn-secondary">📋 Каталог услуг</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><strong>24/7</strong><span>работа сервиса</span></div>
                <div class="hero-stat"><strong>₽</strong><span>оплата в рублях</span></div>
                <div class="hero-stat"><strong>⚡</strong><span>быстрый старт</span></div>
            </div>
        </div>
        <div class="hero-visual animate-in animate-in-delay-2">
            <div class="card card-premium hero-card">
                <h3>🎯 Как это работает</h3>
                <ol class="steps">
                    <li>Регистрация и подтверждение email</li>
                    <li>Пополнение баланса или оплата заказа</li>
                    <li>Выбор услуги и оформление</li>
                    <li>Отслеживание статуса в кабинете</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header reveal">
            <h2 class="section-title">Почему Boosterino</h2>
            <p class="muted">Премиальный сервис с прозрачными ценами и полным контролем над заказами</p>
        </div>
        <div class="features-grid">
            <article class="card feature-card reveal">
                <span class="feature-icon">💰</span>
                <h3>Честные цены в ₽</h3>
                <p>Прозрачное ценообразование с наценкой. Видите стоимость до заказа - без сюрпризов.</p>
            </article>
            <article class="card feature-card reveal">
                <span class="feature-icon">📊</span>
                <h3>Личный кабинет</h3>
                <p>История заказов, баланс, рефилл и отмена - всё под контролем в одном месте.</p>
            </article>
            <article class="card feature-card reveal">
                <span class="feature-icon">💳</span>
                <h3>Безопасная оплата</h3>
                <p>Пополнение через ЮMoney или прямая оплата каждого заказа - вы выбираете.</p>
            </article>
            <article class="card feature-card reveal">
                <span class="feature-icon">⚡</span>
                <h3>Мгновенный запуск</h3>
                <p>Заказы уходят поставщику автоматически. Статус обновляется в реальном времени.</p>
            </article>
            <article class="card feature-card reveal">
                <span class="feature-icon">🔒</span>
                <h3>Защита данных</h3>
                <p>Шифрование, CSRF-защита и безопасное хранение настроек на сервере.</p>
            </article>
            <article class="card feature-card reveal">
                <span class="feature-icon">🌙</span>
                <h3>Премиум интерфейс</h3>
                <p>Адаптивный дизайн, тёмная и светлая тема, удобно на любом устройстве.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-muted">
    <div class="container">
        <div class="section-header reveal">
            <h2 class="section-title">Частые вопросы</h2>
        </div>
        <div class="faq-list">
            <details class="card faq-item reveal">
                <summary>Нужна ли верификация email?</summary>
                <p>Да, подтверждение email обязательно перед первым заказом - это защищает ваш аккаунт.</p>
            </details>
            <details class="card faq-item reveal">
                <summary>Какие способы оплаты доступны?</summary>
                <p>Предоплаченный баланс или прямая оплата каждого заказа через ЮMoney - выбирайте при оформлении.</p>
            </details>
            <details class="card faq-item reveal">
                <summary>Можно ли отменить заказ?</summary>
                <p>Если услуга поддерживает отмену - кнопка доступна в личном кабинете в списке заказов.</p>
            </details>
        </div>
    </div>
</section>

<section class="section">
    <div class="container reveal">
        <div class="cta-premium">
            <h2 class="section-title">Готовы к росту?</h2>
            <p class="lead">Зарегистрируйтесь на boosterino.ru и оформите первый заказ за пару минут. Премиальное продвижение - без лишних шагов.</p>
            <a href="/register" class="btn btn-lg">✨ Создать аккаунт</a>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
