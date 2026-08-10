<?php
$title = 'Discord';
$enabled = ((int) ($settings['is_enabled'] ?? 0)) === 1;
$notifyMarketListCreated = ((int) ($settings['notify_market_list_created'] ?? 0)) === 1;
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-discord"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Discord</h1>
            <p class="muted">Configure notificações amigaveis para eventos importantes do dashboard.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bell"></span>Configuração de notificações</h2>
    <form method="post" action="/system/discord" class="form-grid">
        <label>
            Status
            <select name="is_enabled" data-discord-enabled>
                <option value="0" <?= !$enabled ? 'selected' : '' ?>>Desativado</option>
                <option value="1" <?= $enabled ? 'selected' : '' ?>>Ativado</option>
            </select>
        </label>
        <label class="discord-webhook-field" style="<?= $enabled ? '' : 'display:none' ?>">
            Webhook do Discord
            <input type="url" name="webhook_url" value="<?= htmlspecialchars((string) ($settings['webhook_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://discord.com/api/webhooks/...">
        </label>
        <div class="soft-panel">
            <strong>Eventos para notificar</strong>
            <label class="checkbox-card">
                <input type="checkbox" name="notify_market_list_created" value="1" <?= $notifyMarketListCreated ? 'checked' : '' ?>>
                <span>Notificar quando criar lista do mercado</span>
            </label>
            <p class="muted">Novos eventos serão adicionados aqui conforme o sistema ganhar novas automações.</p>
        </div>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar configuração</button>
        </div>
    </form>
    <form method="post" action="/system/discord/test" class="inline-form total-form">
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
    <h2 class="section-title"><span class="bi bi-robot"></span>Automação sugerida</h2>
    <p class="muted">Para garantir a lista do próximo mês automaticamente, configure o cron do servidor para executar uma vez ao dia:</p>
    <pre><code>php /var/www/dashboard.oficinadodev.com.br/html/bin/ensure_next_market_list.php</code></pre>
</section>

<script>
    const discordStatus = document.querySelector('[data-discord-enabled]');
    const webhookField = document.querySelector('.discord-webhook-field input');
    const syncDiscordRequired = () => {
        if (!webhookField || !discordStatus) {
            return;
        }

        const wrapper = webhookField.closest('.discord-webhook-field');
        const enabled = discordStatus.value === '1';
        webhookField.required = enabled;
        if (wrapper) {
            wrapper.style.display = enabled ? '' : 'none';
        }
    };

    discordStatus?.addEventListener('change', syncDiscordRequired);
    syncDiscordRequired();
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
