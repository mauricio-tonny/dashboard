<?php
$title = 'A receber';
$formatMoney = static fn (float $value): string => format_money_for_user($value, $user ?? null, true);
$formatDate = static fn (string $value): string => (new DateTimeImmutable($value))->format('d/m/Y');
$maxIncomeAmount = $incomeSummary === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $incomeSummary));
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-arrow-down-left-circle"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>A receber</h1>
            <p class="muted">Visualizacao das entradas importadas da planilha para <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions month-actions">
        <a class="month-nav-button" href="<?= htmlspecialchars($previousMonthUrl, ENT_QUOTES, 'UTF-8') ?>"><span class="bi bi-chevron-left"></span>Mes anterior</a>
        <a class="month-nav-button is-primary" href="<?= htmlspecialchars($nextWorkMonthUrl, ENT_QUOTES, 'UTF-8') ?>"><span class="bi bi-calendar2-check"></span>Proximo recebimento</a>
        <a class="month-nav-button" href="<?= htmlspecialchars($nextMonthUrl, ENT_QUOTES, 'UTF-8') ?>">Proximo mes<span class="bi bi-chevron-right"></span></a>
    </div>
</section>

<section class="finance-summary-grid">
    <article class="card finance-summary-card receivable-summary-card">
        <span class="muted">Total recebido</span>
        <strong><?= $formatMoney((float) $summary['total_amount']) ?></strong>
        <small><?= (int) $summary['entries_count'] ?> entrada(s) no periodo</small>
    </article>
    <article class="card finance-summary-card receivable-summary-card">
        <span class="muted">Salario Mauricio</span>
        <strong><?= $formatMoney((float) $summary['mauricio_amount']) ?></strong>
        <small>&nbsp;</small>
    </article>
    <article class="card finance-summary-card receivable-summary-card">
        <span class="muted">Salario Karina</span>
        <strong><?= $formatMoney((float) $summary['karina_amount']) ?></strong>
        <small>&nbsp;</small>
    </article>
    <article class="card finance-summary-card receivable-summary-card">
        <span class="muted">Outras entradas</span>
        <strong><?= $formatMoney((float) $summary['other_amount']) ?></strong>
        <small>&nbsp;</small>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-funnel"></span>Filtros</h2>
    <form method="get" action="/finance/receivable" class="finance-filter-form">
        <label>
            De
            <input type="month" name="start_month" value="<?= htmlspecialchars($startMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
            Ate
            <input type="month" name="end_month" value="<?= htmlspecialchars($endMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-search"></span>Aplicar filtros</button>
        </div>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-wallet2"></span>Entradas do periodo</h2>
    <?php if ($entries === []): ?>
        <div class="empty-state">
            <span class="bi bi-inbox"></span>
            <strong>Nenhuma entrada encontrada</strong>
            <p class="muted">Ajuste o periodo para consultar outros recebimentos importados.</p>
        </div>
    <?php else: ?>
        <div class="responsive-table">
            <table class="finance-entries-table">
                <thead>
                    <tr>
                        <th>Descricao</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr class="cashflow-row-income">
                            <td><strong><?= htmlspecialchars((string) $entry['description'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($formatDate((string) $entry['entry_date']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="status-pill status-<?= htmlspecialchars((string) $entry['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= (string) $entry['status'] === 'paid' ? 'Recebido' : 'Aberto' ?>
                                </span>
                            </td>
                            <td class="money-cell is-positive"><?= $formatMoney((float) $entry['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bar-chart"></span>Entradas por origem</h2>
    <?php if ($incomeSummary === []): ?>
        <p class="muted">Sem dados para montar o grafico neste periodo.</p>
    <?php else: ?>
        <div class="category-column-chart income-column-chart">
            <?php foreach ($incomeSummary as $row): ?>
                <?php $percent = $maxIncomeAmount <= 0 ? 0 : ((float) $row['total_amount'] / $maxIncomeAmount) * 100; ?>
                <div class="category-column-item">
                    <strong><?= $formatMoney((float) $row['total_amount']) ?></strong>
                    <div class="category-column-track">
                        <span style="height: <?= number_format(max(6, $percent), 2, '.', '') ?>%"></span>
                    </div>
                    <span><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></span>
                    <small><?= (int) $row['entries_count'] ?> entrada(s)</small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-checklist"></span>Resumo por origem</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Origem</th>
                    <th>Entradas</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incomeSummary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['entries_count'] ?></td>
                        <td><?= $formatMoney((float) $row['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($incomeSummary === []): ?>
                    <tr>
                        <td colspan="3">Sem entradas no periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
