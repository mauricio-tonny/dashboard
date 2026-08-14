<?php
$title = 'Fluxo de caixa';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$formatDate = static fn (string $value): string => (new DateTimeImmutable($value))->format('d/m/Y');
$cashflowBalance = 0.0;
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-receipt-cutoff"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Fluxo de caixa</h1>
            <p class="muted">Extrato financeiro com saldo acumulado de <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/cashflow';
$showCategoryFilter = false;
$showVendorFilter = false;
require base_path('views/reports/_finance_filters.php');
?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-receipt-cutoff"></span>Extrato do periodo</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descricao</th>
                    <th>Valor</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cashflowRows as $row): ?>
                    <?php
                    $isIncome = (string) $row['type'] === 'income';
                    $signedAmount = $isIncome ? (float) $row['amount'] : -(float) $row['amount'];
                    $cashflowBalance += $signedAmount;
                    $balanceClass = $cashflowBalance >= 0 ? 'is-positive' : 'is-negative';
                    ?>
                    <tr class="<?= $isIncome ? 'cashflow-row-income' : 'cashflow-row-expense' ?>">
                        <td><?= htmlspecialchars($formatDate((string) $row['entry_date']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="cashflow-type-pill <?= $isIncome ? 'is-income' : 'is-expense' ?>">
                                <?= $isIncome ? 'Entrada' : 'Saida' ?>
                            </span>
                        </td>
                        <td><strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td class="money-cell <?= $isIncome ? 'is-positive' : 'is-negative' ?>"><?= $formatMoney($signedAmount) ?></td>
                        <td class="money-cell <?= $balanceClass ?>"><?= $formatMoney($cashflowBalance) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($cashflowRows === []): ?>
                    <tr>
                        <td colspan="5">Sem movimentacoes no periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4">Saldo final</th>
                    <th class="money-cell <?= $cashflowBalance >= 0 ? 'is-positive' : 'is-negative' ?>"><?= $formatMoney($cashflowBalance) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
