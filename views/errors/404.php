<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card reveal" style="max-width:440px;margin:0 auto;text-align:center">
            <div style="font-size:4rem;margin-bottom:1rem">🔍</div>
            <h1>404</h1>
            <p class="muted">Страница не найдена. Возможно, она была перемещена или удалена.</p>
            <a href="/" class="btn btn-primary">🏠 На главную</a>
            <p class="auth-links"><a href="/services">Каталог услуг</a></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
