<?php
$title = 'Extrato Karina';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$formatDate = static fn (string $value): string => (new DateTimeImmutable($value))->format('d/m/Y');
$balance = (float) ($summary['balance_amount'] ?? 0);
$balanceClass = $balance >= 0 ? 'is-positive' : 'is-negative';
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-arrow-left-right"></span>
        <div>
            <span class="badge">Relatórios</span>
            <h1>Extrato Karina</h1>
            <p class="muted">Controle geral de crédito x débito importado da aba KARINA.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<section class="finance-summary-grid">
    <article class="card finance-summary-card">
        <span class="muted">Total de débitos</span>
        <strong class="is-negative"><?= $formatMoney($summary['debit_amount'] ?? 0) ?></strong>
        <small>Compras ou empréstimos registrados</small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Total de créditos</span>
        <strong class="is-positive"><?= $formatMoney($summary['credit_amount'] ?? 0) ?></strong>
        <small>Valores repassados/abatidos</small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Saldo</span>
        <strong class="<?= $balanceClass ?>"><?= $formatMoney($balance) ?></strong>
        <small><?= $balance < 0 ? 'Karina está em débito' : 'Sem débito em aberto' ?></small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Movimentações</span>
        <strong><?= (int) ($summary['entries_count'] ?? 0) ?></strong>
        <small>Registros no histórico completo</small>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-receipt-cutoff"></span>Movimentações</h2>
    <div class="responsive-table">
        <table class="finance-entries-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Conta</th>
                    <th>Pago em</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $isCredit = (string) $row['movement_type'] === 'credit'; ?>
                    <tr class="<?= $isCredit ? 'cashflow-row-income' : 'cashflow-row-expense' ?>">
                        <td><?= htmlspecialchars($formatDate((string) $row['transaction_date']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="cashflow-type-pill <?= $isCredit ? 'is-income' : 'is-expense' ?>">
                                <?= $isCredit ? 'Crédito' : 'Débito' ?>
                            </span>
                        </td>
                        <td><strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string) ($row['paid_account'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['category_name'] ?? 'Sem categoria'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="money-cell <?= $isCredit ? 'is-positive' : 'is-negative' ?>">
                            <?= $formatMoney($isCredit ? (float) $row['amount'] : -(float) $row['amount']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="6">Sem movimentações registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-checklist"></span>Resumo por categoria</h2>
    <div class="responsive-table">
        <table class="finance-entries-table karina-category-summary-table">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="count-cell">Movimentações</th>
                    <th class="money-cell">Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorySummary as $row): ?>
                    <?php
                    $categoryBalance = (float) $row['credit_amount'] - (float) $row['debit_amount'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="count-cell"><?= (int) $row['entries_count'] ?></td>
                        <td class="money-cell <?= $categoryBalance >= 0 ? 'is-positive' : 'is-negative' ?>"><?= $formatMoney($categoryBalance) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($categorySummary === []): ?>
                    <tr>
                        <td colspan="3">Sem categorias no período.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
