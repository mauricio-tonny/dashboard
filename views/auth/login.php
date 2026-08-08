<?php
$title = 'Login';
ob_start();
?>
<section class="card" style="max-width: 480px; margin: 40px auto;">
    <div style="margin-bottom: 24px;">
        <img src="/assets/brand/logo.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>" alt="Financas Dashboard Financeiro" style="display: block; width: min(100%, 320px); height: auto;">
    </div>
    <p class="muted">Acesse sua area financeira com seguranca.</p>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/login">
        <label>
            E-mail
            <input type="email" name="email" required>
        </label>

        <label>
            Senha
            <input type="password" name="password" required>
        </label>

        <button type="submit">Entrar</button>
    </form>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
