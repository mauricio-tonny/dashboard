<?php
$title = 'Pago x recebido';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$formatMonth = static function (string $value): string {
    $date = new DateTimeImmutable($value . '-01');

    return $date->format('m/Y');
};
$maxPaidVsReceived = $paidVsReceivedRows === [] ? 0.0 : max(array_map(static fn (array $row): float => max((float) $row['income_amount'], (float) $row['expense_amount']), $paidVsReceivedRows));
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-columns-gap"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Pago x recebido</h1>
            <p class="muted">Comparativo mensal de entradas e saidas em <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/paid-vs-received';
$showCategoryFilter = false;
$showVendorFilter = false;
require base_path('views/reports/_finance_filters.php');
?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-columns-gap"></span>Comparativo mensal</h2>
    <p class="muted">O modulo Recebido vai alimentar melhor este relatorio nas proximas etapas.</p>
    <?php if ($paidVsReceivedRows === []): ?>
        <p class="muted">Sem dados financeiros no periodo.</p>
    <?php else: ?>
        <div class="paired-bar-chart">
            <?php foreach ($paidVsReceivedRows as $row): ?>
                <?php
                $incomeHeight = $maxPaidVsReceived <= 0 ? 0 : ((float) $row['income_amount'] / $maxPaidVsReceived) * 150;
                $expenseHeight = $maxPaidVsReceived <= 0 ? 0 : ((float) $row['expense_amount'] / $maxPaidVsReceived) * 150;
                ?>
                <div class="paired-bar-item">
                    <div class="paired-bar-columns">
                        <span class="paired-bar-income" style="height: <?= (int) max(8, $incomeHeight) ?>px"></span>
                        <span class="paired-bar-expense" style="height: <?= (int) max(8, $expenseHeight) ?>px"></span>
                    </div>
                    <strong><?= htmlspecialchars($formatMonth($row['month_key']), ENT_QUOTES, 'UTF-8') ?></strong>
                    <small>Recebido <?= $formatMoney($row['income_amount']) ?></small>
                    <small>Saida <?= $formatMoney($row['expense_amount']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Recebido</th>
                        <th>Despesas</th>
                        <th>Pago</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paidVsReceivedRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($formatMonth($row['month_key']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $formatMoney($row['income_amount']) ?></td>
                            <td><?= $formatMoney($row['expense_amount']) ?></td>
                            <td><?= $formatMoney($row['paid_expense_amount']) ?></td>
                            <td><?= $formatMoney($row['balance_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
