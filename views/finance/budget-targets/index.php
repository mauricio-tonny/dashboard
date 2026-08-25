<?php
$title = 'Metas de gastos';
$formatMoney = static fn (mixed $value): string => format_money_for_user($value, $user);
$formatPercent = static fn (mixed $value): string => $value === null ? '-' : number_format((float) $value, 2, ',', '.') . '%';
$statusLabels = [
    'within' => ['Dentro da meta', 'status-paid'],
    'above_target' => ['Acima da meta', 'status-open'],
    'below_minimum' => ['Abaixo do minimo', 'status-open'],
    'tracking' => ['Acompanhar', 'status-planned'],
    'unconfigured' => ['Sem meta', 'status-planned'],
];
$controlLabels = [
    'maximum' => 'Maximo',
    'minimum' => 'Minimo',
    'track' => 'Acompanhar',
];
$referenceRange = static function (array $target) use ($formatPercent): string {
    $min = $target['reference_min_percent'] ?? null;
    $max = $target['reference_max_percent'] ?? null;

    if ($min === null && $max === null) {
        return '-';
    }

    if ($min !== null && $max !== null) {
        return $formatPercent($min) . ' a ' . $formatPercent($max);
    }

    return $min !== null ? 'Min. ' . $formatPercent($min) : 'Max. ' . $formatPercent($max);
};
$targetUsage = static function (array $target): float {
    $targetAmount = $target['target_amount'] ?? null;
    $spentAmount = $target['spent_amount'] ?? null;

    if ($targetAmount === null || $spentAmount === null || (float) $targetAmount <= 0) {
        return 0.0;
    }

    return min(140.0, max(0.0, ((float) $spentAmount / (float) $targetAmount) * 100));
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bullseye"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>Metas de gastos</h1>
            <p class="muted">Compare o gasto realizado por categoria com a meta configurada para o periodo.</p>
        </div>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-funnel"></span>Periodo</h2>
    <form class="grid report-filter budget-target-filter" method="get" action="/finance/budget-targets">
        <label>
            De
            <input type="month" name="start_month" value="<?= htmlspecialchars($startMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
            Ate
            <input type="month" name="end_month" value="<?= htmlspecialchars($endMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <button type="submit"><span class="bi bi-search"></span>Aplicar filtros</button>
    </form>
</section>

<section class="report-summary-grid budget-target-summary-grid">
    <article class="card report-summary-card">
        <small>Dentro da meta</small>
        <strong><?= (int) ($summary['within'] ?? 0) ?></strong>
        <span class="muted">Categorias controladas</span>
    </article>
    <article class="card report-summary-card">
        <small>Em atencao</small>
        <strong><?= (int) ($summary['above_target'] ?? 0) + (int) ($summary['below_minimum'] ?? 0) ?></strong>
        <span class="muted">Acima do maximo ou abaixo do minimo</span>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-list-check"></span>Resultado por categoria</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Referencia</th>
                    <th>Realizado</th>
                    <th>Meta</th>
                    <th>Uso da meta</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($targets as $target): ?>
                    <?php
                    [$statusLabel, $statusClass] = $statusLabels[$target['status']] ?? ['Indefinido', 'status-planned'];
                    $usage = $targetUsage($target);
                    $meterWidth = min(100.0, $usage);
                    $meterClass = in_array($target['status'], ['above_target', 'below_minimum'], true) ? 'is-alert' : 'is-ok';
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) $target['category_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted"><?= htmlspecialchars($controlLabels[$target['control_type']] ?? (string) $target['control_type'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $target['reference_label'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="muted"><?= htmlspecialchars($referenceRange($target), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?= $formatMoney($target['spent_amount']) ?><br>
                            <?php if ((float) $target['spent_amount'] > 0): ?>
                                <span class="muted"><?= htmlspecialchars($formatPercent($target['actual_percent']), ENT_QUOTES, 'UTF-8') ?> do recebido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $target['target_amount'] === null ? '-' : $formatMoney($target['target_amount']) ?><br>
                            <span class="muted"><?= htmlspecialchars($formatPercent($target['target_percent']), ENT_QUOTES, 'UTF-8') ?> configurado</span>
                        </td>
                        <td>
                            <?php if ((float) $target['spent_amount'] > 0): ?>
                                <div class="target-meter <?= htmlspecialchars($meterClass, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(number_format($usage, 2, ',', '.') . '% da meta', ENT_QUOTES, 'UTF-8') ?>">
                                    <span style="width: <?= htmlspecialchars((string) $meterWidth, ENT_QUOTES, 'UTF-8') ?>%"></span>
                                </div>
                                <small class="muted"><?= htmlspecialchars(number_format($usage, 2, ',', '.') . '%', ENT_QUOTES, 'UTF-8') ?></small>
                            <?php else: ?>
                                <span class="muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($targets === []): ?>
                    <tr>
                        <td colspan="6" class="muted">Nenhuma despesa ou meta encontrada para o periodo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
