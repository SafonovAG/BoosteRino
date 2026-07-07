<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card">
            <h1>Новый пароль</h1>
            <form id="reset-form" class="form">
                <input type="hidden" name="token" value="<?= \App\Core\View::e($token ?? '') ?>">
                <label>Новый пароль<input type="password" name="password" required minlength="8"></label>
                <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
            </form>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
$scripts = ['/assets/js/auth.js'];
include dirname(__DIR__) . '/layouts/main.php';
