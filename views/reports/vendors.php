<?php
$title = 'Relatorio por fornecedor';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$maxVendorAmount = $vendorSummary === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $vendorSummary));
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-person-lines-fill"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Relatorio por fornecedor</h1>
            <p class="muted">Agrupamento de despesas por fornecedor em <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/vendors';
require base_path('views/reports/_finance_filters.php');
?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-person-lines-fill"></span>Fornecedores</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th>Lancamentos</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Aberto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendorSummary as $row): ?>
                    <?php $percent = $maxVendorAmount <= 0 ? 0 : ((float) $row['total_amount'] / $maxVendorAmount) * 100; ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) $row['vendor_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="report-mini-bar"><span style="width: <?= number_format(max(4, $percent), 2, '.', '') ?>%"></span></span>
                        </td>
                        <td><?= (int) $row['entries_count'] ?></td>
                        <td class="money-cell"><?= $formatMoney($row['total_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['paid_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['open_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($vendorSummary === []): ?>
                    <tr>
                        <td colspan="5">Sem fornecedores para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
