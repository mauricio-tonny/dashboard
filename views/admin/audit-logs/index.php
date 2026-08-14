<?php
$title = 'Logs de Auditoria';
ob_start();

$labels = [
    'login_success' => 'Login realizado',
    'login_failed' => 'Falha de login',
    'logout' => 'Logout',
    'session_timeout' => 'Logout por inatividade',
    'page_viewed' => 'Tela acessada',
    'report_viewed' => 'Relatorio visualizado',
    'user_created' => 'Usuário criado',
    'user_updated' => 'Usuário atualizado',
    'user_blocked' => 'Usuário bloqueado',
    'user_unblocked' => 'Usuário desbloqueado',
    'shopping_market_list_saved' => 'Lista de mercado preparada',
    'shopping_market_list_finished' => 'Lista de mercado finalizada',
    'shopping_market_list_reopened' => 'Lista de mercado reaberta',
    'shopping_market_list_deleted' => 'Lista de mercado excluída',
    'shopping_market_item_created' => 'Item de mercado criado',
    'shopping_market_item_updated' => 'Item de mercado atualizado',
    'shopping_market_item_checked' => 'Item de mercado marcado',
    'shopping_market_item_unchecked' => 'Item de mercado desmarcado',
    'shopping_market_item_deleted' => 'Item de mercado removido',
    'shopping_market_invoice_uploaded' => 'Nota de mercado anexada',
    'shopping_market_invoice_imported' => 'XML de mercado importado',
    'shopping_market_invoice_import_failed' => 'Falha ao importar XML de mercado',
    'shopping_market_invoice_access_key_saved' => 'Chave de acesso de mercado salva',
    'shopping_wish_item_created' => 'Item de compras criado',
    'shopping_wish_item_updated' => 'Item de compras atualizado',
    'shopping_wish_item_purchased' => 'Item de compras comprado',
    'shopping_wish_item_reopened' => 'Item de compras reaberto',
    'shopping_wish_item_deleted' => 'Item de compras removido',
    'shopping_setting_saved' => 'Configuração de compras salva',
    'shopping_setting_enabled' => 'Configuração de compras reativada',
    'shopping_setting_disabled' => 'Configuração de compras desativada',
    'shopping_vehicle_saved' => 'Veículo salvo',
    'shopping_vehicle_enabled' => 'Veículo reativado',
    'shopping_vehicle_disabled' => 'Veículo desativado',
    'discord_webhook_tested' => 'Teste de webhook do Discord',
    'spreadsheet_url_saved' => 'Link da planilha salvo',
    'spreadsheet_url_tested' => 'Link da planilha testado',
    'spreadsheet_url_removed' => 'Link da planilha removido',
    'contact_created' => 'Contato criado',
    'contact_updated' => 'Contato atualizado',
    'contact_enabled' => 'Contato reativado',
    'contact_disabled' => 'Contato desativado',
    'user_permissions_updated' => 'Permissoes do usuario atualizadas',
];
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-journal-text"></span>
        <div>
            <span class="badge">Administrador</span>
            <h1>Logs de auditoria</h1>
            <p class="muted">Acompanhe acessos e ações importantes realizadas no sistema.</p>
        </div>
    </div>
</section>

<?php
$totalPageViews = array_sum(array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $topPages ?? []));
$totalReportViews = array_sum(array_map(static fn (array $row): int => (int) ($row['total'] ?? 0), $topReports ?? []));
?>

<section class="report-summary-grid">
    <article class="card report-summary-card">
        <small>Acessos em telas</small>
        <strong><?= $totalPageViews ?></strong>
        <span class="muted"><?= count($topPages ?? []) ?> tela(s) nos ultimos 30 dias</span>
    </article>
    <article class="card report-summary-card">
        <small>Relatorios gerados</small>
        <strong><?= $totalReportViews ?></strong>
        <span class="muted"><?= count($topReports ?? []) ?> relatorio(s) nos ultimos 30 dias</span>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-graph-up"></span>Uso das telas</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Tela</th>
                    <th>Acessos</th>
                    <th>Usuarios</th>
                    <th>Ultimo acesso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($topPages ?? []) as $row): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) ($row['label'] ?: $row['path']), ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted"><?= htmlspecialchars((string) ($row['path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= (int) $row['total'] ?></td>
                        <td><?= (int) $row['users_count'] ?></td>
                        <td><?= htmlspecialchars((string) ($row['last_seen_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($topPages ?? []) === []): ?>
                    <tr>
                        <td colspan="4">Sem acessos registrados no periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bar-chart-line"></span>Uso dos relatorios</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Relatorio</th>
                    <th>Acessos</th>
                    <th>Usuarios</th>
                    <th>Ultimo acesso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($topReports ?? []) as $row): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) ($row['label'] ?: $row['path']), ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted"><?= htmlspecialchars((string) ($row['path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= (int) $row['total'] ?></td>
                        <td><?= (int) $row['users_count'] ?></td>
                        <td><?= htmlspecialchars((string) ($row['last_seen_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($topReports ?? []) === []): ?>
                    <tr>
                        <td colspan="4">Sem relatorios registrados no periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-clock-history"></span>Eventos recentes</h2>
    <div class="responsive-table">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Acao</th>
                    <th>Usuário</th>
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
                            <?= htmlspecialchars($log['user_name'] ?? 'Sistema/não identificado', ENT_QUOTES, 'UTF-8') ?><br>
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
