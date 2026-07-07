<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card reveal" style="max-width:440px;margin:0 auto">
            <h1>🔓 Восстановление пароля</h1>
            <p class="muted">Введите email - мы отправим ссылку для сброса пароля.</p>
            <form id="forgot-form" class="form">
                <label>📧 Email<input type="email" name="email" required></label>
                <button type="submit" class="btn btn-primary btn-block">Отправить ссылку</button>
            </form>
            <p class="auth-links"><a href="/login">Вернуться ко входу</a></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
