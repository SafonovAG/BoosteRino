<?php
use App\Core\View;

ob_start();
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="badge">Премиальный SMM-сервис</span>
            <h1>Продвижение в соцсетях - быстро и прозрачно</h1>
            <p class="lead">Подписчики, лайки, просмотры и активность для Instagram, Telegram, VK и других платформ. Оплата в рублях, личный кабинет, мгновенный старт.</p>
            <div class="hero-actions">
                <a href="https://boosterino.ru/register" class="btn btn-primary">Начать на boosterino.ru</a>
                <a href="/services" class="btn btn-secondary">Каталог услуг</a>
            </div>
        </div>
        <div class="hero-card card">
            <h3>Как это работает</h3>
            <ol class="steps">
                <li>Регистрация и подтверждение email</li>
                <li>Пополнение баланса или оплата заказа</li>
                <li>Выбор услуги и оформление</li>
                <li>Отслеживание статуса в кабинете</li>
            </ol>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Почему Boosterino</h2>
        <div class="features-grid">
            <article class="card feature-card">
                <h3>Честные цены в ₽</h3>
                <p>Прозрачное ценообразование с наценкой. Видите стоимость до заказа.</p>
            </article>
            <article class="card feature-card">
                <h3>Личный кабинет</h3>
                <p>История заказов, баланс, refill и отмена - всё под контролем.</p>
            </article>
            <article class="card feature-card">
                <h3>Безопасная оплата</h3>
                <p>Пополнение через ЮMoney или оплата заказа напрямую.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-muted">
    <div class="container">
        <h2 class="section-title">FAQ</h2>
        <div class="faq-list">
            <details class="card faq-item">
                <summary>Нужна ли верификация email?</summary>
                <p>Да, подтверждение email обязательно перед первым заказом.</p>
            </details>
            <details class="card faq-item">
                <summary>Какие способы оплаты?</summary>
                <p>Предоплаченный баланс или прямая оплата каждого заказа через ЮMoney.</p>
            </details>
            <details class="card faq-item">
                <summary>Можно ли отменить заказ?</summary>
                <p>Если услуга поддерживает отмену - кнопка доступна в личном кабинете.</p>
            </details>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
