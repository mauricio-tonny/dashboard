<?php
$title = 'Dashboard';
$maxExpense = max(array_map(static fn (array $item): float => (float) $item['amount'], $annualExpenses)) ?: 1;
$formatMoney = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$marketTotal = (int) ($marketSummary['item_count'] ?? 0);
$marketChecked = (int) ($marketSummary['checked_count'] ?? 0);
$marketProgress = $marketTotal > 0 ? round(($marketChecked / $marketTotal) * 100) : 0;
$pieColors = ['#0f3770', '#14b8a6', '#38bdf8', '#f59e0b', '#ef4444', '#84cc16', '#6366f1', '#ec4899', '#64748b', '#22c55e'];
$annualCategoryTotal = array_reduce($annualCategoryExpenses, static fn (float $carry, array $item): float => $carry + (float) $item['amount'], 0.0);
$pieStops = [];
$pieCursor = 0.0;

foreach ($annualCategoryExpenses as $index => $item) {
    $amount = (float) $item['amount'];
    $percent = $annualCategoryTotal <= 0 ? 0 : ($amount / $annualCategoryTotal) * 100;
    $nextCursor = $pieCursor + $percent;
    $color = $pieColors[$index % count($pieColors)];
    $pieStops[] = "{$color} " . number_format($pieCursor, 3, '.', '') . "% " . number_format($nextCursor, 3, '.', '') . "%";
    $pieCursor = $nextCursor;
}

$pieGradient = $pieStops === [] ? '#e8f0f5' : implode(', ', $pieStops);
$months = [
    1 => 'janeiro',
    2 => 'fevereiro',
    3 => 'marco',
    4 => 'abril',
    5 => 'maio',
    6 => 'junho',
    7 => 'julho',
    8 => 'agosto',
    9 => 'setembro',
    10 => 'outubro',
    11 => 'novembro',
    12 => 'dezembro',
];
$today = new DateTimeImmutable('now');
$todayLabel = sprintf(
    '%d de %s de %s',
    (int) $today->format('d'),
    $months[(int) $today->format('n')],
    $today->format('Y')
);
ob_start();
?>
<section class="dashboard-hero">
    <div>
        <span class="badge"><?= htmlspecialchars($user->role->label(), ENT_QUOTES, 'UTF-8') ?></span>
        <h1>Ola, <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted">
            Hoje e dia <?= htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8') ?>. Abaixo esta um resumo das suas proximas despesas e dos itens mapeados para compra.
        </p>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <span class="metric-icon bi bi-calendar2-week"></span>
        <small>Contas a pagar</small>
        <strong><?= $formatMoney($payableSummary['total_amount'] ?? 0) ?></strong>
        <?php if (($payableSummary['current_or_previous_open_amount'] ?? 0) > 0): ?>
            <p class="muted">
                Inclui <?= $formatMoney($payableSummary['current_or_previous_open_amount']) ?> em aberto ate o mes atual.
            </p>
        <?php endif; ?>
    </article>

    <article class="metric-card">
        <span class="metric-icon bi bi-basket2"></span>
        <small>Mercado proximo mes</small>
        <strong><?= $marketChecked ?>/<?= $marketTotal ?> itens</strong>
        <p class="muted"><?= $marketProgress ?>% da lista marcada. Total: <?= htmlspecialchars($marketSummary['total_amount'] === null ? '-' : $formatMoney($marketSummary['total_amount']), ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="metric-card">
        <span class="metric-icon bi bi-house-heart"></span>
        <small>Para casa</small>
        <strong><?= count($pendingHomeItems) ?> pendentes</strong>
        <div class="mini-list metric-mini-list">
            <?php foreach ($pendingHomeItems as $item): ?>
                <div class="mini-list-item mini-list-item-compact">
                    <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if ($pendingHomeItems === []): ?>
                <p class="muted">Nenhum item pendente para casa.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <h2>Despesas por DR anual</h2>
        <p class="muted">Distribuicao anual das despesas importadas por categoria/DR.</p>
        <?php if ($annualCategoryTotal <= 0): ?>
            <div class="pie-placeholder">
                <span class="bi bi-pie-chart"></span>
            </div>
            <p class="muted">Sem despesas importadas para o ano atual.</p>
        <?php else: ?>
            <div class="annual-dr-chart">
                <div class="annual-dr-pie" style="background: conic-gradient(<?= htmlspecialchars($pieGradient, ENT_QUOTES, 'UTF-8') ?>)">
                    <span><?= $formatMoney($annualCategoryTotal) ?></span>
                </div>
                <div class="annual-dr-legend">
                    <?php foreach (array_slice($annualCategoryExpenses, 0, 10) as $index => $item): ?>
                        <?php $percent = $annualCategoryTotal <= 0 ? 0 : ((float) $item['amount'] / $annualCategoryTotal) * 100; ?>
                        <div class="annual-dr-legend-item">
                            <span style="background: <?= htmlspecialchars($pieColors[$index % count($pieColors)], ENT_QUOTES, 'UTF-8') ?>"></span>
                            <strong><?= htmlspecialchars((string) $item['category_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= number_format($percent, 1, ',', '.') ?>% | <?= $formatMoney((float) $item['amount']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </article>

    <article class="card">
        <h2>Despesas anual</h2>
        <p class="muted">Somatoria mensal das despesas importadas para o ano atual.</p>
        <div class="bar-chart">
            <?php foreach ($annualExpenses as $item): ?>
                <?php $height = max(8, round(((float) $item['amount'] / $maxExpense) * 140)); ?>
                <div class="bar-chart-item">
                    <div class="bar-track">
                        <span class="bar-fill" style="height: <?= (int) $height ?>px"></span>
                    </div>
                    <small><?= htmlspecialchars($item['month'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
