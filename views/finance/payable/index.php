<?php
$title = 'A pagar';
$formatMoney = static fn (float $value): string => 'R$&nbsp;' . number_format($value, 2, ',', '.');
$formatDate = static fn (string $value): string => (new DateTimeImmutable($value))->format('d/m/Y');
$maxCategoryAmount = $categorySummary === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $categorySummary));
$selectedCategoryCount = count($categoryIds);
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-arrow-up-right-circle"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>A pagar</h1>
            <p class="muted">Visualizacao das despesas importadas da planilha para <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions month-actions">
        <a class="month-nav-button" href="<?= htmlspecialchars($previousMonthUrl, ENT_QUOTES, 'UTF-8') ?>"><span class="bi bi-chevron-left"></span>Mes anterior</a>
        <a class="month-nav-button is-primary" href="<?= htmlspecialchars($nextWorkMonthUrl, ENT_QUOTES, 'UTF-8') ?>"><span class="bi bi-calendar2-check"></span>Proximo vencimento</a>
        <a class="month-nav-button" href="<?= htmlspecialchars($nextMonthUrl, ENT_QUOTES, 'UTF-8') ?>">Proximo mes<span class="bi bi-chevron-right"></span></a>
    </div>
</section>

<section class="finance-summary-grid">
    <article class="card finance-summary-card">
        <span class="muted">Total de despesas</span>
        <strong><?= $formatMoney((float) $summary['total_amount']) ?></strong>
        <small>
            <?= (int) $summary['entries_count'] ?> lancamentos no periodo
        </small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Falta pagar</span>
        <strong><?= $formatMoney((float) $summary['open_amount']) ?></strong>
        <small>
            Periodo + pendencias anteriores
            <?php if ((int) $summary['previous_open_count'] > 0): ?>
                (<?= (int) $summary['previous_open_count'] ?> antigas)
            <?php endif; ?>
        </small>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Ja pago</span>
        <strong><?= $formatMoney((float) $summary['paid_amount']) ?></strong>
    </article>
    <article class="card finance-summary-card">
        <span class="muted">Ultimas parcelas</span>
        <strong><?= $formatMoney((float) $summary['last_installments_amount']) ?></strong>
        <small>Parcelas finais identificadas no periodo</small>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-funnel"></span>Filtros</h2>
    <form method="get" action="/finance/payable" class="finance-filter-form">
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
        <details class="category-multi-filter" <?= $selectedCategoryCount > 0 ? 'open' : '' ?>>
            <summary>
                <span>Categorias</span>
                <small><?= $selectedCategoryCount === 0 ? 'Todas' : $selectedCategoryCount . ' selecionada(s)' ?></small>
            </summary>
            <p class="muted"><?= $selectedCategoryCount === 0 ? 'Todas as categorias entram no filtro enquanto nenhuma estiver marcada.' : 'Somente as categorias marcadas entram no filtro.' ?></p>
            <div class="category-chip-grid">
                <?php foreach ($categories as $category): ?>
                    <?php $id = (int) $category['id']; ?>
                    <label class="category-filter-chip">
                        <input type="checkbox" name="category_ids[]" value="<?= $id ?>" <?= in_array($id, $categoryIds, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </details>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-list-check"></span>Despesas do periodo</h2>
    <?php if ($entries === []): ?>
        <div class="empty-state">
            <span class="bi bi-inbox"></span>
            <strong>Nenhuma despesa encontrada</strong>
            <p class="muted">Ajuste o periodo ou a categoria para consultar outros lancamentos.</p>
        </div>
    <?php else: ?>
        <div class="responsive-table">
            <table class="finance-entries-table">
                <thead>
                    <tr>
                        <th>Conta</th>
                        <th>Fornecedor</th>
                        <th>Categoria</th>
                        <th>Parcela</th>
                        <th>Status</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string) $entry['description'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td><?= htmlspecialchars((string) ($entry['vendor_name'] ?? 'Sem fornecedor'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($entry['category_name'] ?? 'Sem categoria'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ($entry['installment_current'] !== null && $entry['installment_total'] !== null): ?>
                                    <?= (int) $entry['installment_current'] ?>/<?= (int) $entry['installment_total'] ?>
                                    <?php if ((int) $entry['is_last_installment'] === 1): ?>
                                        <span class="last-installment-pill">Final</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="cash-payment-pill">A vista</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill status-<?= htmlspecialchars((string) $entry['status'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= (string) $entry['status'] === 'paid' ? 'Pago' : 'Aberto' ?>
                                </span>
                            </td>
                            <td class="money-cell"><?= $formatMoney((float) $entry['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bar-chart"></span>Despesas por categoria</h2>
    <?php if ($categorySummary === []): ?>
        <p class="muted">Sem dados para montar o grafico neste periodo.</p>
    <?php else: ?>
        <div class="category-column-chart">
            <?php foreach ($categorySummary as $row): ?>
                <?php $percent = $maxCategoryAmount <= 0 ? 0 : ((float) $row['total_amount'] / $maxCategoryAmount) * 100; ?>
                <div class="category-column-item">
                    <strong><?= $formatMoney((float) $row['total_amount']) ?></strong>
                    <div class="category-column-track">
                        <span style="height: <?= number_format(max(6, $percent), 2, '.', '') ?>%"></span>
                    </div>
                    <span><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <small><?= (int) $row['entries_count'] ?> lancamentos</small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-checklist"></span>Resumo por categoria</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Lancamentos</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorySummary as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['entries_count'] ?></td>
                        <td><?= $formatMoney((float) $row['total_amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
