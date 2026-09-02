<?php
$title = 'Segurança e Analytics';
$recaptchaEnabled = ((int) ($settings['recaptcha_is_enabled'] ?? 0)) === 1;
$analyticsEnabled = ((int) ($settings['analytics_is_enabled'] ?? 0)) === 1;
$hasSecret = (bool) ($settings['has_recaptcha_secret_key'] ?? false);
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-shield-check"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Segurança e Analytics</h1>
            <p class="muted">Controle protecoes de login, rastreamento GA4 e recursos para auditoria do dashboard.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="/system/security" class="security-settings-form">
    <section class="card section-card security-card">
        <h2 class="section-title"><span class="bi bi-robot"></span>reCAPTCHA invisível</h2>
        <p class="muted">Protege o login contra bots usando o score do Google reCAPTCHA v3.</p>

        <input type="hidden" name="recaptcha_is_enabled" value="0">
        <label class="discord-toggle-card">
            <input type="checkbox" name="recaptcha_is_enabled" value="1" data-security-toggle="recaptcha" <?= $recaptchaEnabled ? 'checked' : '' ?>>
            <span class="discord-toggle-switch" aria-hidden="true"></span>
            <span>
                <strong>Habilitar reCAPTCHA no login</strong>
                <small>Quando ativo, toda tentativa de login precisa ser validada no backend.</small>
            </span>
        </label>

        <div class="discord-dependent-settings" data-security-dependent="recaptcha" style="<?= $recaptchaEnabled ? '' : 'display:none' ?>">
            <div class="form-grid">
                <label>
                    Site Key
                    <input type="text" name="recaptcha_site_key" value="<?= htmlspecialchars((string) ($settings['recaptcha_site_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Chave pública do reCAPTCHA">
                </label>
                <label>
                    Secret Key
                    <input type="password" name="recaptcha_secret_key" value="" placeholder="<?= $hasSecret ? htmlspecialchars((string) ($settings['masked_recaptcha_secret_key'] ?? 'Secret salva'), ENT_QUOTES, 'UTF-8') : 'Chave secreta do reCAPTCHA' ?>">
                    <small class="muted"><?= $hasSecret ? 'Deixe em branco para manter a chave atual.' : 'A chave será armazenada criptografada.' ?></small>
                </label>
                <label>
                    Score mínimo
                    <input type="number" name="recaptcha_min_score" min="0" max="1" step="0.01" value="<?= htmlspecialchars((string) ($settings['recaptcha_min_score'] ?? '0.50'), ENT_QUOTES, 'UTF-8') ?>">
                    <small class="muted">Referência inicial sugerida: 0.50.</small>
                </label>
            </div>
            <input type="hidden" name="keep_recaptcha_secret_key" value="<?= $hasSecret ? '1' : '0' ?>">
        </div>
    </section>

    <section class="card section-card security-card">
        <h2 class="section-title"><span class="bi bi-graph-up-arrow"></span>Google Analytics 4</h2>
        <p class="muted">Insere o Google tag no cabeçalho do sistema apenas quando a medição estiver habilitada.</p>

        <input type="hidden" name="analytics_is_enabled" value="0">
        <label class="discord-toggle-card">
            <input type="checkbox" name="analytics_is_enabled" value="1" data-security-toggle="analytics" <?= $analyticsEnabled ? 'checked' : '' ?>>
            <span class="discord-toggle-switch" aria-hidden="true"></span>
            <span>
                <strong>Habilitar tag do Analytics</strong>
                <small>Use somente o Measurement ID do GA4, no formato G-XXXXXXXXXX.</small>
            </span>
        </label>

        <div class="discord-dependent-settings" data-security-dependent="analytics" style="<?= $analyticsEnabled ? '' : 'display:none' ?>">
            <label>
                Measurement ID
                <input type="text" name="analytics_measurement_id" value="<?= htmlspecialchars((string) ($settings['analytics_measurement_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="G-XXXXXXXXXX">
            </label>
        </div>
    </section>

    <section class="card section-card security-card">
        <h2 class="section-title"><span class="bi bi-door-closed"></span>Controles ativos</h2>
        <div class="soft-panel">
            <strong>Sessão e login</strong>
            <p class="muted">O sistema ja bloqueia sessoes simultaneas, redireciona por inatividade e limita tentativas excessivas de login por IP/e-mail.</p>
        </div>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar configuracao</button>
        </div>
    </section>
</form>

<script>
    document.querySelectorAll('[data-security-toggle]').forEach((toggle) => {
        const target = toggle.dataset.securityToggle;
        const dependent = document.querySelector(`[data-security-dependent="${target}"]`);

        const syncVisibility = () => {
            if (dependent) {
                dependent.style.display = toggle.checked ? '' : 'none';
            }
        };

        toggle.addEventListener('change', syncVisibility);
        syncVisibility();
    });
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
