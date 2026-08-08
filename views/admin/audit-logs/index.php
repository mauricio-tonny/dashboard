<?php
$title = 'Logs de Auditoria';
ob_start();

$labels = [
    'login_success' => 'Login realizado',
    'login_failed' => 'Falha de login',
    'logout' => 'Logout',
    'session_timeout' => 'Logout por inatividade',
    'user_created' => 'Usuario criado',
    'user_updated' => 'Usuario atualizado',
    'user_blocked' => 'Usuario bloqueado',
    'user_unblocked' => 'Usuario desbloqueado',
    'shopping_market_list_saved' => 'Lista de mercado preparada',
    'shopping_market_list_finished' => 'Lista de mercado finalizada',
    'shopping_market_item_created' => 'Item de mercado criado',
    'shopping_market_item_updated' => 'Item de mercado atualizado',
    'shopping_market_item_checked' => 'Item de mercado marcado',
    'shopping_market_item_unchecked' => 'Item de mercado desmarcado',
    'shopping_market_item_deleted' => 'Item de mercado removido',
    'shopping_wish_item_created' => 'Item de compras criado',
    'shopping_wish_item_updated' => 'Item de compras atualizado',
    'shopping_wish_item_purchased' => 'Item de compras comprado',
    'shopping_wish_item_reopened' => 'Item de compras reaberto',
    'shopping_wish_item_deleted' => 'Item de compras removido',
    'shopping_setting_saved' => 'Configuracao de compras salva',
    'shopping_setting_enabled' => 'Configuracao de compras reativada',
    'shopping_setting_disabled' => 'Configuracao de compras desativada',
    'shopping_vehicle_saved' => 'Veiculo salvo',
    'shopping_vehicle_enabled' => 'Veiculo reativado',
    'shopping_vehicle_disabled' => 'Veiculo desativado',
    'contact_created' => 'Contato criado',
    'contact_updated' => 'Contato atualizado',
    'contact_enabled' => 'Contato reativado',
    'contact_disabled' => 'Contato desativado',
];
?>
<section class="card">
    <span class="badge">Administrador</span>
    <h1>Logs de auditoria</h1>
    <p class="muted">Acompanhe acessos e acoes importantes realizadas no sistema.</p>

    <div class="actions">
        <a href="/"><button class="inline-button" type="button">Voltar ao dashboard</button></a>
    </div>
</section>

<section class="card">
    <div style="overflow-x: auto;">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Acao</th>
                    <th>Usuario</th>
                    <th>Origem</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php $metadata = json_decode((string) ($log['metadata'] ?? ''), true) ?: []; ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($labels[$log['action']] ?? $log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($log['user_name'] ?? 'Sistema/nao identificado', ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="muted"><?= htmlspecialchars($log['user_email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($log['entity_type'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($metadata === []): ?>
                                <span class="muted">-</span>
                            <?php else: ?>
                                <code><?= htmlspecialchars(json_encode($metadata, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></code>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
