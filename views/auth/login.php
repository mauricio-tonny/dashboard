<?php
$title = 'Login';
ob_start();
?>
<section class="card" style="max-width: 480px; margin: 40px auto;">
    <h1>Dashboard Financeiro</h1>
    <p class="muted">Base inicial com autenticacao, papeis de acesso e integracao planejada com Excel.</p>

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
