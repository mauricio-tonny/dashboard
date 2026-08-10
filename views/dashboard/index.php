<?php
$title = 'Dashboard';
$maxExpense = max(array_map(static fn (array $item): float => (float) $item['amount'], $annualExpenses)) ?: 1;
$formatMoney = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$marketTotal = (int) ($marketSummary['item_count'] ?? 0);
$marketChecked = (int) ($marketSummary['checked_count'] ?? 0);
$marketProgress = $marketTotal > 0 ? round(($marketChecked / $marketTotal) * 100) : 0;
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
            Hoje e dia <?= htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8') ?>. Abaixo está um resumo das suas proximas despesas e dos itens mapeados para compra.
        </p>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <span class="metric-icon bi bi-calendar2-week"></span>
        <small>Contas a pagar</small>
        <strong><?= $formatMoney($upcoming['next_month_estimated_expenses']) ?></strong>
        <p class="muted">Próximo mês. Caso não exista baixa no mês atual/anterior, este card seguirá aparecendo como alerta.</p>
    </article>

    <article class="metric-card">
        <span class="metric-icon bi bi-basket2"></span>
        <small>Mercado próximo mês</small>
        <strong><?= $marketChecked ?>/<?= $marketTotal ?> itens</strong>
        <p class="muted"><?= $marketProgress ?>% da lista marcada. Total: <?= htmlspecialchars($marketSummary['total_amount'] === null ? '-' : $formatMoney($marketSummary['total_amount']), ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="metric-card">
        <span class="metric-icon bi bi-house-heart"></span>
        <small>Para casa</small>
        <strong><?= count($pendingHomeItems) ?> pendentes</strong>
        <p class="muted">Ultimos 10 itens ainda não confirmados.</p>
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
        <h2>Despesas por DR</h2>
        <div class="pie-placeholder">
            <span class="bi bi-pie-chart"></span>
        </div>
        <p class="muted">Espaco reservado para o grafico pizza de despesas por categoria/DR.</p>
    </article>

    <article class="card">
        <h2>Despesas anual</h2>
        <p class="muted">Indicador mensal em barras. Os valores reais entram quando concluirmos a integração financeira.</p>
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
