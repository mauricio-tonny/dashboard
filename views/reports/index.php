<?php
$title = 'Relatórios';
$formatMoney = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$amounts = array_map(static fn (array $row): float => (float) $row['total_amount'], $marketRows);
$maxAmount = $amounts === [] ? 1.0 : max($amounts);
$periodLabel = (new DateTimeImmutable($startDate))->format('d/m/Y') . ' até ' . (new DateTimeImmutable($endDate))->format('d/m/Y');
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bar-chart-line"></span>
        <div>
            <span class="badge">Relatórios</span>
            <h1>Relatório de mercado</h1>
            <p class="muted">Compare o valor total das compras de mercado dentro do período selecionado.</p>
        </div>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-calendar-range"></span>Período de analise</h2>
    <form method="get" action="/reports/market" class="inline-form report-filter">
        <label>
            Data inicial
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            Data final
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <button class="inline-button" type="submit"><span class="bi bi-funnel"></span>Gerar relatório</button>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-basket2"></span>Mercado no período</h2>
    <p class="muted">Resultado consolidado de <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>

    <div class="report-summary-grid">
        <div class="report-summary-card">
            <small>Total do período</small>
            <strong><?= htmlspecialchars($formatMoney($marketSummary['total']), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="report-summary-card">
            <small>Media mensal</small>
            <strong><?= htmlspecialchars($formatMoney($marketSummary['average']), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="report-summary-card">
            <small>Maior mês</small>
            <strong><?= htmlspecialchars($formatMoney($marketSummary['max']), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="report-summary-card">
            <small>Menor mês</small>
            <strong><?= htmlspecialchars($formatMoney($marketSummary['min']), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>

    <?php if ($marketRows === []): ?>
        <p class="muted">Nenhuma lista de mercado finalizada com valor neste período.</p>
    <?php else: ?>
        <div class="report-bar-chart" aria-label="Grafico de barras com valores mensais de mercado">
            <?php foreach ($marketRows as $row): ?>
                <?php
                $amount = (float) $row['total_amount'];
                $height = max(12, (int) round(($amount / $maxAmount) * 190));
                ?>
                <div class="report-bar-item">
                    <small><?= htmlspecialchars($formatMoney($amount), ENT_QUOTES, 'UTF-8') ?></small>
                    <span class="report-bar-track">
                        <span class="report-bar-fill" style="height: <?= $height ?>px"></span>
                    </span>
                    <strong><?= htmlspecialchars($row['month_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Listas</th>
                        <th>Total</th>
                        <th>Media por lista</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($marketRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['month_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $row['list_count'] ?></td>
                            <td><?= htmlspecialchars($formatMoney($row['total_amount']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($formatMoney($row['average_amount']), ENT_QUOTES, 'UTF-8') ?></td>
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
