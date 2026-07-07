<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card">
            <?php if (!empty($success)): ?>
                <h1>Email подтверждён</h1>
                <p>Можно войти и оформить заказ.</p>
                <a href="/login" class="btn btn-primary btn-block">Войти</a>
            <?php else: ?>
                <h1>Ошибка</h1>
                <p class="muted">Ссылка недействительна или устарела.</p>
                <a href="https://boosterino.ru/register" class="btn btn-secondary btn-block">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
