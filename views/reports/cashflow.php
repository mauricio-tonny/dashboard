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
                    <th>Origem/Categoria</th>
                    <th>Status</th>
                    <th>Valor</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cashflowRows as $row): ?>
                    <?php
                    $signedAmount = (string) $row['type'] === 'income' ? (float) $row['amount'] : -(float) $row['amount'];
                    $cashflowBalance += $signedAmount;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDate((string) $row['entry_date']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (string) $row['type'] === 'income' ? 'Entrada' : 'Saida' ?></td>
                        <td><strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string) ($row['vendor_name'] !== 'Sem fornecedor' ? $row['vendor_name'] : $row['category_name']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (string) $row['status'] === 'paid' ? 'Pago' : 'Aberto' ?></td>
                        <td class="money-cell"><?= $formatMoney($signedAmount) ?></td>
                        <td class="money-cell"><?= $formatMoney($cashflowBalance) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($cashflowRows === []): ?>
                    <tr>
                        <td colspan="7">Sem movimentacoes no periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6">Saldo final</th>
                    <th class="money-cell"><?= $formatMoney($cashflowBalance) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
