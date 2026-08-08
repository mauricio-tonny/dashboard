<?php
$title = 'Login';
$bodyClass = 'auth-page';
ob_start();
?>
<section class="auth-shell">
    <div class="auth-brand-panel">
        <div>
            <h1 class="auth-brand-title">Controle financeiro com clareza.</h1>
            <p class="auth-brand-copy">Um painel privado para acompanhar lancamentos, previsoes e responsabilidades financeiras.</p>

            <div class="auth-brand-grid" aria-hidden="true">
                <span class="auth-brand-bar"></span>
                <span class="auth-brand-bar"></span>
                <span class="auth-brand-bar"></span>
            </div>
        </div>

        <p class="auth-brand-footnote">Dashboard pessoal protegido por autenticacao.</p>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <img class="auth-logo" src="/assets/brand/logo.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>" alt="Financas Dashboard Financeiro">
            <p class="auth-lead">Acesse sua area financeira com seguranca.</p>

            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" action="/login">
                <label>
                    E-mail
                    <input type="email" name="email" autocomplete="email" required>
                </label>

                <label>
                    Senha
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="auth-submit" type="submit">Entrar</button>
            </form>
        </div>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
