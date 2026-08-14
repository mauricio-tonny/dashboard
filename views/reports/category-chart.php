<?php
$title = 'Grafico por categoria';
$formatMoney = static fn ($value): string => format_money_for_user($value, $user ?? null);
$maxCategoryAmount = $categorySummary === [] ? 0.0 : max(array_map(static fn (array $row): float => (float) $row['total_amount'], $categorySummary));
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bar-chart"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Grafico por categoria</h1>
            <p class="muted">Comparativo visual das categorias em <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/reports"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/reports/category-chart';
require base_path('views/reports/_finance_filters.php');
?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-bar-chart"></span>Despesas por categoria</h2>
    <?php if ($categorySummary === []): ?>
        <p class="muted">Sem despesas para montar o grafico neste periodo.</p>
    <?php else: ?>
        <div class="category-column-chart">
            <?php foreach ($categorySummary as $row): ?>
                <?php $percent = $maxCategoryAmount <= 0 ? 0 : ((float) $row['total_amount'] / $maxCategoryAmount) * 100; ?>
                <div class="category-column-item">
                    <strong><?= $formatMoney($row['total_amount']) ?></strong>
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
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
