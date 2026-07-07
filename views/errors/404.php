<?php
ob_start();
?>
<section class="section auth-section">
    <div class="container auth-container">
        <div class="card auth-card" style="text-align:center">
            <h1>404</h1>
            <p class="muted">Страница не найдена</p>
            <a href="/" class="btn btn-primary">На главную</a>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layouts/main.php';
