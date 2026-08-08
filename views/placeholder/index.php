<?php
ob_start();
?>
<section class="card placeholder-card">
    <span class="badge"><?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?></span>
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="muted"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="placeholder-illustration">
        <span class="bi bi-tools"></span>
        <strong>Em desenho funcional</strong>
        <small>Esta area ja esta posicionada no menu para manter a UX organizada enquanto definimos as regras.</small>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
