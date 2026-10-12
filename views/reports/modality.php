<?php
$title = 'Relatório por modalidade';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$totalAmount = array_reduce($modalitySummary, static fn (float $carry, array $row): float => $carry + (float) $row['total_amount'], 0.0);
$maxAmount = $modalitySummary === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $modalitySummary));
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-credit-card-2-front"></span>
        <div>
            <span class="badge">Relatórios financeiros</span>
            <h1>Relatório por modalidade</h1>
            <p class="muted">Comparativo das despesas por modalidade em <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/modality';
$showCategoryFilter = false;
$showVendorFilter = false;
require base_path('views/reports/_finance_filters.php');
?>

<section class="finance-summary-grid">
    <article class="card finance-summary-card">
        <span class="muted">Total no período</span>
        <strong><?= $formatMoney($totalAmount) ?></strong>
        <small><?= array_sum(array_column($modalitySummary, 'entries_count')) ?> lançamentos</small>
    </article>
    <?php foreach ($modalitySummary as $row): ?>
        <article class="card finance-summary-card">
            <span class="muted"><?= htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= $formatMoney($row['total_amount']) ?></strong>
            <small><?= (int) $row['entries_count'] ?> lançamento(s)</small>
        </article>
    <?php endforeach; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bar-chart"></span>Comparativo por modalidade</h2>
    <?php if ($modalitySummary === []): ?>
        <p class="muted">Sem despesas para o período selecionado.</p>
    <?php else: ?>
        <div class="category-column-chart">
            <?php foreach ($modalitySummary as $row): ?>
                <?php $percent = $maxAmount <= 0 ? 0 : ((float) $row['total_amount'] / $maxAmount) * 100; ?>
                <div class="category-column-item">
                    <strong><?= $formatMoney($row['total_amount']) ?></strong>
                    <div class="category-column-track">
                        <span style="height: <?= number_format(max(6, $percent), 2, '.', '') ?>%"></span>
                    </div>
                    <span><?= htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <small><?= (int) $row['entries_count'] ?> lançamentos</small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-list-check"></span>Detalhamento</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Modalidade</th>
                    <th>Lançamentos</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Aberto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modalitySummary as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= (int) $row['entries_count'] ?></td>
                        <td class="money-cell"><?= $formatMoney($row['total_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['paid_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['open_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($modalitySummary === []): ?>
                    <tr><td colspan="5">Sem dados para o período selecionado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
