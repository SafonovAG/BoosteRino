<?php
use App\Services\ServiceLogo;
$platforms = ServiceLogo::platforms();
ob_start();
?>
<section class="shop-hero shop-section">
    <div class="container shop-hero-grid">
        <div class="shop-hero-content reveal">
            <span class="shop-hero-badge">⚡ Прямой поставщик SMM-услуг</span>
            <h1 class="shop-hero-title">
                Быстрая накрутка<br>
                <span class="gradient-text">в социальных сетях</span>
            </h1>
            <p class="shop-hero-lead">Увеличьте подписчиков, просмотры и реакции по выгодным ценам. Прозрачный каталог, личный кабинет и оплата в рублях через ЮMoney.</p>
            <div class="shop-hero-actions">
                <a href="/services" class="btn btn-primary btn-lg">🛒 Открыть каталог</a>
                <a href="/register" class="btn btn-secondary btn-lg">Создать аккаунт</a>
            </div>
            <div class="shop-hero-perks">
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Цены за 1000 ед.</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Рефилл и отмена</span>
                <span class="shop-hero-perk"><span class="shop-hero-perk-icon">✓</span> Статус в кабинете</span>
            </div>
        </div>
        <div class="shop-hero-visual reveal">
            <div class="shop-order-card">
                <div class="shop-order-card-header">
                    <span>Ваш заказ</span>
                    <span class="shop-order-status">Выполняется</span>
                </div>
                <div class="shop-order-item">
                    <img src="/assets/images/logo/telegram.svg" alt="Telegram" width="40" height="40">
                    <div>
                        <strong>Telegram Подписчики</strong>
                        <div class="shop-order-progress"><div style="width:45%"></div></div>
                        <small>450 из 1000</small>
                    </div>
                </div>
                <div class="shop-order-price">
                    <span class="shop-order-price-old">320 ₽</span>
                    <span class="shop-order-price-new">199 ₽</span>
                    <span class="shop-order-discount">-38%</span>
                </div>
                <div class="shop-order-actions">
                    <button type="button" class="btn btn-secondary btn-sm" disabled>Повторить</button>
                    <button type="button" class="btn btn-ghost btn-sm" disabled>Отменить</button>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip shop-section-compact">
    <div class="container trust-strip-grid">
        <div class="trust-stat reveal"><strong>24/7</strong><span>работа сервиса</span></div>
        <div class="trust-stat reveal"><strong>₽</strong><span>только рубли</span></div>
        <div class="trust-stat reveal"><strong>API</strong><span>поставщик Twiboost</span></div>
        <div class="trust-stat reveal"><strong>100+</strong><span>видов услуг</span></div>
    </div>
</section>

<section class="shop-section shop-section-compact">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Платформы в каталоге</h2>
            <p class="section-subtitle">Выберите соцсеть и оформите заказ как в интернет-магазине</p>
        </div>
        <div class="platforms-row reveal">
            <?php foreach (array_slice($platforms, 1) as $p): ?>
                <a href="/services?platform=<?= \App\Core\View::e($p['slug']) ?>" class="platform-chip platform-chip-lg">
                    <img src="<?= \App\Core\View::e($p['logo']) ?>" alt="<?= \App\Core\View::e($p['name']) ?>" width="40" height="40">
                    <span><?= \App\Core\View::e($p['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="shop-section" id="featured">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Популярные товары</h2>
            <a href="/services" class="section-link">Весь каталог →</a>
        </div>
        <div id="featured-products" class="product-grid">
            <div class="product-card skeleton"></div>
            <div class="product-card skeleton"></div>
            <div class="product-card skeleton"></div>
            <div class="product-card skeleton"></div>
        </div>
    </div>
</section>

<section class="shop-section shop-section-muted" id="how">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Как заказать</h2>
            <p class="section-subtitle">Три простых шага - как в любом интернет-магазине</p>
        </div>
        <div class="usecase-grid">
            <article class="usecase-card reveal">
                <div class="usecase-icon">🛍️</div>
                <h3>1. Выберите товар</h3>
                <p>Откройте каталог, найдите услугу по платформе или категории. Цена указана за 1000 единиц.</p>
            </article>
            <article class="usecase-card reveal">
                <div class="usecase-icon">💳</div>
                <h3>2. Оплатите заказ</h3>
                <p>С баланса или через ЮMoney. Пополните кошелёк заранее или оплатите каждый заказ отдельно.</p>
            </article>
            <article class="usecase-card reveal">
                <div class="usecase-icon">📈</div>
                <h3>3. Следите за статусом</h3>
                <p>В личном кабинете видно прогресс. Доступны рефилл и отмена, если услуга это поддерживает.</p>
            </article>
        </div>
    </div>
</section>

<section class="shop-section">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Почему Boosterino</h2>
        </div>
        <div class="compare-table-wrap reveal">
            <table class="compare-table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="compare-highlight">Boosterino</th>
                        <th>Обычные посредники</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Цены</td><td class="compare-highlight">✅ Прямой поставщик</td><td>❌ Высокая наценка</td></tr>
                    <tr><td>Каталог</td><td class="compare-highlight">✅ Актуальный API</td><td>❌ Устаревшие прайсы</td></tr>
                    <tr><td>Оплата</td><td class="compare-highlight">✅ ЮMoney + баланс</td><td>❌ Сложные схемы</td></tr>
                    <tr><td>Кабинет</td><td class="compare-highlight">✅ Заказы и история</td><td>❌ Минимум функций</td></tr>
                    <tr><td>Мобильная версия</td><td class="compare-highlight">✅ Адаптивный магазин</td><td>❌ Неудобно с телефона</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="shop-section shop-section-muted">
    <div class="container">
        <div class="section-head reveal">
            <h2 class="section-title">Отзывы покупателей</h2>
        </div>
        <div class="reviews-grid">
            <blockquote class="review-card reveal">
                <div class="review-stars">★★★★★</div>
                <p>Удобный каталог как в магазине - выбрал Telegram подписчиков, оплатил с баланса, заказ ушёл сразу.</p>
                <footer>Алексей, владелец канала</footer>
            </blockquote>
            <blockquote class="review-card reveal">
                <div class="review-stars">★★★★★</div>
                <p>Цены в рублях, всё прозрачно до оплаты. Кабинет показывает статус - не нужно писать в поддержку.</p>
                <footer>Марина, SMM-специалист</footer>
            </blockquote>
            <blockquote class="review-card reveal">
                <div class="review-stars">★★★★☆</div>
                <p>Заказывал VK и YouTube - логотипы платформ в каталоге сразу видно, не перепутаешь услугу.</p>
                <footer>Дмитрий, маркетолог</footer>
            </blockquote>
        </div>
    </div>
</section>

<section class="shop-section" id="faq">
    <div class="container container-narrow">
        <div class="section-head reveal">
            <h2 class="section-title">Частые вопросы</h2>
        </div>
        <div class="shop-faq reveal">
            <details class="shop-faq-item">
                <summary>Когда начнётся выполнение заказа?</summary>
                <p>После оплаты заказ автоматически отправляется поставщику. Скорость зависит от типа услуги и нагрузки - статус виден в кабинете.</p>
            </details>
            <details class="shop-faq-item">
                <summary>Какие способы оплаты?</summary>
                <p>Предоплата на баланс или оплата каждого заказа через ЮMoney - выбираете при оформлении.</p>
            </details>
            <details class="shop-faq-item">
                <summary>Что такое рефилл?</summary>
                <p>Восстановление списаний со стороны соцсети. Доступен для услуг с пометкой «Рефилл» в каталоге.</p>
            </details>
            <details class="shop-faq-item">
                <summary>Нужна ли верификация email?</summary>
                <p>Да, подтвердите почту перед первым заказом - это защищает ваш аккаунт.</p>
            </details>
        </div>
    </div>
</section>

<section class="shop-section shop-section-compact">
    <div class="container reveal">
        <div class="shop-cta-banner">
            <h2>Готовы оформить первый заказ?</h2>
            <p>Зарегистрируйтесь, пополните баланс и выберите услугу в каталоге - как в любом онлайн-магазине.</p>
            <div class="shop-cta-banner-actions">
                <a href="/register" class="btn btn-lg">Регистрация</a>
                <a href="/services" class="btn btn-secondary btn-lg">Смотреть каталог</a>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/shop-home.js'];
include dirname(__DIR__) . '/layouts/main.php';
