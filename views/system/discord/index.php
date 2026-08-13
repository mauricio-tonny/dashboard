<?php
$title = 'Discord';
$enabled = ((int) ($settings['is_enabled'] ?? 0)) === 1;
$notifyMarketListCreated = ((int) ($settings['notify_market_list_created'] ?? 0)) === 1;
$notifySpreadsheetImportChanged = ((int) ($settings['notify_spreadsheet_import_changed'] ?? 0)) === 1;
$notifySpreadsheetImportUnchanged = ((int) ($settings['notify_spreadsheet_import_unchanged'] ?? 0)) === 1;
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-discord"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Discord</h1>
            <p class="muted">Configure notificacoes amigaveis para eventos importantes do dashboard.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card discord-card">
    <h2 class="section-title"><span class="bi bi-bell"></span>Configuracao de notificacoes</h2>
    <form method="post" action="/system/discord" class="discord-settings-form">
        <input type="hidden" name="is_enabled" value="0">
        <label class="discord-toggle-card">
            <input type="checkbox" name="is_enabled" value="1" data-discord-enabled <?= $enabled ? 'checked' : '' ?>>
            <span class="discord-toggle-switch" aria-hidden="true"></span>
            <span>
                <strong>Notificacoes do Discord</strong>
                <small>Ative ou desative todos os avisos enviados pelo dashboard.</small>
            </span>
        </label>

        <div class="discord-dependent-settings" data-discord-dependent style="<?= $enabled ? '' : 'display:none' ?>">
            <label class="discord-webhook-field">
                Webhook do Discord
                <input type="url" name="webhook_url" value="<?= htmlspecialchars((string) ($settings['webhook_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://discord.com/api/webhooks/...">
            </label>

            <div class="soft-panel discord-events-panel">
                <strong>Eventos para notificar</strong>
                <p class="muted">Escolha quais acontecimentos devem gerar mensagens no Discord.</p>
                <label class="checkbox-card">
                    <input type="checkbox" name="notify_market_list_created" value="1" <?= $notifyMarketListCreated ? 'checked' : '' ?>>
                    <span>Notificar quando criar lista do mercado</span>
                </label>
                <label class="checkbox-card">
                    <input type="checkbox" name="notify_spreadsheet_import_changed" value="1" <?= $notifySpreadsheetImportChanged ? 'checked' : '' ?>>
                    <span>Notificar quando a importacao automatica da planilha gravar ou atualizar lancamentos</span>
                </label>
                <label class="checkbox-card">
                    <input type="checkbox" name="notify_spreadsheet_import_unchanged" value="1" <?= $notifySpreadsheetImportUnchanged ? 'checked' : '' ?>>
                    <span>Notificar quando a importacao automatica da planilha rodar sem alteracoes</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar configuracao</button>
        </div>
    </form>

    <form method="post" action="/system/discord/test" class="inline-form total-form" data-discord-dependent style="<?= $enabled ? '' : 'display:none' ?>">
        <div>
            <strong>Teste do webhook</strong>
            <p class="muted">Envia uma mensagem simples usando o webhook salvo acima.</p>
        </div>
        <button class="inline-button button-light" type="submit" <?= trim((string) ($settings['webhook_url'] ?? '')) === '' ? 'disabled' : '' ?>>
            <span class="bi bi-send"></span>Enviar teste
        </button>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-robot"></span>Automacao sugerida</h2>
    <p class="muted">O Discord e acionado pelas rotinas do scheduler interno. Para manter as automacoes ativas, o servidor deve executar:</p>
    <pre><code>php /var/www/dashboard.oficinadodev.com.br/html/bin/schedule_run.php</code></pre>
</section>

<script>
    const discordStatus = document.querySelector('[data-discord-enabled]');
    const webhookField = document.querySelector('.discord-webhook-field input');
    const dependentSettings = document.querySelectorAll('[data-discord-dependent]');

    const syncDiscordRequired = () => {
        if (!webhookField || !discordStatus) {
            return;
        }

        const enabled = discordStatus.checked;
        webhookField.required = enabled;
        dependentSettings.forEach((item) => {
            item.style.display = enabled ? '' : 'none';
        });
    };

    discordStatus?.addEventListener('change', syncDiscordRequired);
    syncDiscordRequired();
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
