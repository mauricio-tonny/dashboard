<?php
$title = 'Gerenciar planilha';
$hasUrl = (bool) ($settings['has_url'] ?? false);
$maskedUrl = (string) ($settings['masked_url'] ?? '');
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-file-earmark-spreadsheet"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Gerenciar planilha</h1>
            <p class="muted">Cadastre o link compartilhado da planilha para leitura segura dos dados financeiros.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$encryptionReady): ?>
    <div class="notice notice-error">
        Configure a variável <code>APP_KEY</code> no <code>.env</code> antes de salvar o link da planilha.
    </div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-shield-lock"></span>Link da planilha</h2>
    <form method="post" action="/system/spreadsheet" class="form-grid">
        <label>
            URL compartilhada
            <input
                type="url"
                name="spreadsheet_url"
                value=""
                placeholder="<?= $hasUrl ? 'Link salvo. Preencha apenas se quiser substituir.' : 'https://1drv.ms/x/...' ?>"
                <?= $encryptionReady ? '' : 'disabled' ?>
                required
            >
        </label>
        <div class="soft-panel">
            <strong>Status atual</strong>
            <?php if ($hasUrl): ?>
                <p class="muted">Existe um link salvo e criptografado no banco.</p>
                <p><code><?= htmlspecialchars($maskedUrl, ENT_QUOTES, 'UTF-8') ?></code></p>
            <?php else: ?>
                <p class="muted">Nenhum link de planilha salvo ainda.</p>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" <?= $encryptionReady ? '' : 'disabled' ?>>
                <span class="bi bi-save"></span>Salvar link
            </button>
        </div>
    </form>

    <div class="inline-form total-form">
        <div>
            <strong>Validação de acesso</strong>
            <p class="muted">Testa se o link salvo pode ser acessado e se parece entregar um arquivo Excel.</p>
        </div>
        <form method="post" action="/system/spreadsheet/test">
            <button class="inline-button button-light" type="submit" <?= $hasUrl ? '' : 'disabled' ?>>
                <span class="bi bi-link-45deg"></span>Testar link
            </button>
        </form>
    </div>

    <form method="post" action="/system/spreadsheet/remove" class="inline-form total-form" onsubmit="return confirm('Remover o link salvo da planilha?');">
        <div>
            <strong>Remover vínculo</strong>
            <p class="muted">Remove o link criptografado do banco. A planilha original no OneDrive não será alterada.</p>
        </div>
        <button class="inline-button button-danger" type="submit" <?= $hasUrl ? '' : 'disabled' ?>>
            <span class="bi bi-trash"></span>Remover link
        </button>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-info-circle"></span>Segurança</h2>
    <p class="muted">O link é usado apenas para leitura neste primeiro momento. Ele é criptografado antes de ser salvo e não aparece completo na interface ou nos logs de auditoria.</p>
    <p class="muted">Se houver suspeita de vazamento, revogue o compartilhamento no OneDrive e salve um novo link aqui.</p>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
