<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="auth-wrap">
            <div class="card card-premium auth-promo reveal">
                <span class="badge badge-glow">✨ Премиум аккаунт</span>
                <h2>Начните продвижение сегодня</h2>
                <p class="muted">Создайте аккаунт за минуту и получите доступ к полному каталогу SMM-услуг.</p>
                <ul class="promo-list">
                    <li><span>🎯</span> Сотни услуг для соцсетей</li>
                    <li><span>💳</span> Оплата через ЮMoney</li>
                    <li><span>📱</span> Удобный личный кабинет</li>
                </ul>
            </div>
            <div class="card auth-card reveal">
                <h1>🚀 Регистрация</h1>
                <form id="register-form" class="form">
                    <label>📧 Email<input type="email" name="email" required autocomplete="email"></label>
                    <label>🔑 Пароль (мин. 8 символов)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                    <button type="submit" class="btn btn-primary btn-block">Создать аккаунт</button>
                </form>
                <p class="auth-links"><a href="/login">Уже есть аккаунт? Войти</a></p>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
