<?php
$title = 'Login';
$bodyClass = 'auth-page';
$securityPublicSettings = is_array($GLOBALS['security_public_settings'] ?? null) ? $GLOBALS['security_public_settings'] : [];
$recaptchaEnabled = (bool) ($securityPublicSettings['recaptcha_is_enabled'] ?? false);
$recaptchaSiteKey = (string) ($securityPublicSettings['recaptcha_site_key'] ?? '');
ob_start();
?>
<section class="auth-shell">
    <div class="auth-brand-panel">
        <div>
            <h1 class="auth-brand-title">Controle financeiro com clareza.</h1>
            <p class="auth-brand-copy">Um painel privado para acompanhar lançamentos, previsoes e responsabilidades financeiras.</p>

            <div class="auth-brand-grid" aria-hidden="true">
                <span class="auth-brand-bar"></span>
                <span class="auth-brand-bar"></span>
                <span class="auth-brand-bar"></span>
            </div>
        </div>

        <p class="auth-brand-footnote">Dashboard pessoal protegido por autenticação.</p>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <img class="auth-logo" src="/assets/brand/logo.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>" alt="Financas Dashboard Financeiro">
            <p class="auth-lead">Acesse sua área financeira com segurança.</p>

            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" action="/login" data-recaptcha-login>
                <label>
                    E-mail
                    <input type="email" name="email" autocomplete="email" required>
                </label>

                <label>
                    Senha
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <?php if ($recaptchaEnabled): ?>
                    <input type="hidden" name="recaptcha_token" data-recaptcha-token>
                <?php endif; ?>

                <button class="auth-submit" type="submit">Entrar</button>
            </form>
        </div>
    </div>
</section>
<?php if ($recaptchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
        document.querySelector('[data-recaptcha-login]')?.addEventListener('submit', (event) => {
            const form = event.currentTarget;
            const tokenInput = form.querySelector('[data-recaptcha-token]');

            if (!tokenInput || tokenInput.value !== '') {
                return;
            }

            event.preventDefault();
            if (typeof grecaptcha === 'undefined') {
                alert('Não foi possível carregar a validação de segurança. Atualize a página e tente novamente.');
                return;
            }

            grecaptcha.ready(() => {
                grecaptcha.execute('<?= htmlspecialchars($recaptchaSiteKey, ENT_QUOTES, 'UTF-8') ?>', { action: 'login' }).then((token) => {
                    tokenInput.value = token;
                    form.submit();
                });
            });
        });
    </script>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
