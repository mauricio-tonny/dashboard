<?php
$title = 'Relatorio por categoria';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$formatDate = static fn (string $value): string => (new DateTimeImmutable($value))->format('d/m/Y');
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-tags"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Relatorio por categoria</h1>
            <p class="muted">Despesas de <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?> filtradas por categoria e fornecedor.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/categories';
require base_path('views/reports/_finance_filters.php');
?>

<section class="finance-summary-grid">
    <article class="card finance-summary-card">
        <span class="muted">Total</span>
        <strong><?= $formatMoney($expenseSummary['total_amount']) ?></strong>
        <small><?= (int) $expenseSummary['entries_count'] ?> lancamentos</small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Pago</span>
        <strong><?= $formatMoney($expenseSummary['paid_amount']) ?></strong>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Aberto</span>
        <strong><?= $formatMoney($expenseSummary['open_amount']) ?></strong>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-list-check"></span>Despesas encontradas</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Conta</th>
                    <th>Fornecedor</th>
                    <th>Categoria</th>
                    <th>Parcela</th>
                    <th>Status</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenseRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($formatDate((string) $row['entry_date']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string) $row['vendor_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if ($row['installment_current'] !== null && $row['installment_total'] !== null): ?>
                                <?= (int) $row['installment_current'] ?>/<?= (int) $row['installment_total'] ?>
                                <?php if ((int) $row['is_last_installment'] === 1): ?>
                                    <span class="last-installment-pill">Final</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="cash-payment-pill">A vista</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (string) $row['status'] === 'paid' ? 'Pago' : 'Aberto' ?></td>
                        <td class="money-cell"><?= $formatMoney($row['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($expenseRows === []): ?>
                    <tr>
                        <td colspan="7">Sem despesas para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-checklist"></span>Resumo por categoria</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Lancamentos</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Aberto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorySummary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['entries_count'] ?></td>
                        <td class="money-cell"><?= $formatMoney($row['total_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['paid_amount']) ?></td>
                        <td class="money-cell"><?= $formatMoney($row['open_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
