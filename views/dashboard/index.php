<?php
$title = 'Dashboard';
$maxExpense = max(array_map(static fn (array $item): float => (float) $item['amount'], $annualExpenses)) ?: 1;
$formatMoney = static fn ($value): string => 'R$ ' . number_format((float) $value, 2, ',', '.');
$marketTotal = (int) ($marketSummary['item_count'] ?? 0);
$marketChecked = (int) ($marketSummary['checked_count'] ?? 0);
$marketProgress = $marketTotal > 0 ? round(($marketChecked / $marketTotal) * 100) : 0;
ob_start();
?>
<section class="dashboard-hero">
    <div>
        <span class="badge"><?= htmlspecialchars($user->role->label(), ENT_QUOTES, 'UTF-8') ?></span>
        <h1>Ola, <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="muted">Visao rapida para decidir o que merece atencao primeiro.</p>
    </div>
    <div class="hero-version">
        <small>Versao</small>
        <strong><?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
</section>

<section class="metric-grid">
    <article class="metric-card">
        <span class="metric-icon bi bi-calendar2-week"></span>
        <small>Contas a pagar</small>
        <strong><?= $formatMoney($upcoming['next_month_estimated_expenses']) ?></strong>
        <p class="muted">Proximo mes. Caso nao exista baixa no mes atual/anterior, este card seguira aparecendo como alerta.</p>
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
        <p class="muted">Ultimos 10 itens ainda nao confirmados.</p>
    </article>
</section>

<section class="dashboard-grid">
    <article class="card">
        <h2>Lista para casa</h2>
        <div class="mini-list">
            <?php foreach ($pendingHomeItems as $item): ?>
                <div class="mini-list-item">
                    <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($item['room_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?> | Prioridade <?= (int) ($item['priority'] ?? 0) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($pendingHomeItems === []): ?>
                <p class="muted">Nenhum item pendente para casa.</p>
            <?php endif; ?>
        </div>
    </article>

    <article class="card">
        <h2>Despesas anual</h2>
        <p class="muted">Indicador mensal em barras. Os valores reais entram quando concluirmos a integracao financeira.</p>
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
