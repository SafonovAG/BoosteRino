<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card reveal" style="max-width:440px;margin:0 auto;text-align:center">
            <?php if (!empty($success)): ?>
                <div style="font-size:3rem;margin-bottom:1rem">✅</div>
                <h1>Email подтверждён</h1>
                <p class="muted">Аккаунт активирован. Можно войти и оформить первый заказ.</p>
                <a href="/login" class="btn btn-primary btn-block">🚀 Войти в кабинет</a>
            <?php else: ?>
                <div style="font-size:3rem;margin-bottom:1rem">❌</div>
                <h1>Ошибка подтверждения</h1>
                <p class="muted">Ссылка недействительна или устарела. Запросите новое письмо при регистрации.</p>
                <a href="/register" class="btn btn-secondary btn-block">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
