<?php
$title = 'Metas por categoria';
$controlLabels = [
    'maximum' => 'Limite máximo',
    'minimum' => 'Meta mínima',
    'track' => 'Acompanhar',
];
$formatPercent = static fn (mixed $value): string => $value === null ? '' : number_format((float) $value, 2, ',', '');
$formatMoneyInput = static fn (mixed $value): string => $value === null ? '' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$baseIncomeValue = $baseIncomeAmount ?? null;
$referenceRange = static function (array $target) use ($formatPercent): string {
    $min = $target['reference_min_percent'] ?? null;
    $max = $target['reference_max_percent'] ?? null;

    if ($min === null && $max === null) {
        return 'Sem parâmetro';
    }

    if ($min !== null && $max !== null) {
        return $formatPercent($min) . '% a ' . $formatPercent($max) . '%';
    }

    return ($min !== null ? 'Min. ' . $formatPercent($min) : 'Max. ' . $formatPercent($max)) . '%';
};
$activeTargets = array_values(array_filter($targets, static fn (array $target): bool => (bool) ($target['is_active'] ?? true)));
$inactiveTargets = array_values(array_filter($targets, static fn (array $target): bool => !((bool) ($target['is_active'] ?? true))));
$renderRows = static function (array $items) use ($controlLabels, $formatPercent, $formatMoneyInput, $referenceRange): void {
    foreach ($items as $target):
        $id = (int) $target['category_id'];
        $isActive = (bool) ($target['is_active'] ?? true);
        ?>
        <tr class="<?= $isActive ? '' : 'is-muted-row' ?>">
            <td>
                <div class="budget-category-cell">
                    <strong><?= htmlspecialchars((string) $target['category_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($target['is_suggested']): ?>
                        <span class="badge">Sugestão inicial</span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="targets[<?= $id ?>][category_id]" value="<?= $id ?>">
                <input type="hidden" name="targets[<?= $id ?>][reference_key]" value="<?= htmlspecialchars((string) $target['reference_key'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="targets[<?= $id ?>][is_active]" value="0">
                <label class="budget-status-switch">
                    <input type="checkbox" name="targets[<?= $id ?>][is_active]" value="1" <?= $isActive ? 'checked' : '' ?>>
                    <span class="budget-status-track" aria-hidden="true"></span>
                    <span class="budget-status-label" data-active-label="Ativa" data-inactive-label="Inativa"><?= $isActive ? 'Ativa' : 'Inativa' ?></span>
                </label>
            </td>
            <td>
                <input type="text" name="targets[<?= $id ?>][reference_label]" value="<?= htmlspecialchars((string) $target['reference_label'], ENT_QUOTES, 'UTF-8') ?>">
            </td>
            <td>
                <div class="inline-fields">
                    <input type="text" name="targets[<?= $id ?>][reference_min_percent]" value="<?= htmlspecialchars($formatPercent($target['reference_min_percent']), ENT_QUOTES, 'UTF-8') ?>" placeholder="Min.">
                    <input type="text" name="targets[<?= $id ?>][reference_max_percent]" value="<?= htmlspecialchars($formatPercent($target['reference_max_percent']), ENT_QUOTES, 'UTF-8') ?>" placeholder="Max.">
                </div>
                <small class="muted"><?= htmlspecialchars($referenceRange($target), ENT_QUOTES, 'UTF-8') ?></small>
            </td>
            <td>
                <select name="targets[<?= $id ?>][control_type]">
                    <?php foreach ($controlLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $target['control_type'] === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" name="targets[<?= $id ?>][target_percent]" value="<?= htmlspecialchars($formatPercent($target['target_percent']), ENT_QUOTES, 'UTF-8') ?>" placeholder="% da renda" data-budget-percent>
                <small class="muted">Percentual salvo</small>
            </td>
            <td>
                <input type="text" name="targets[<?= $id ?>][desired_amount]" value="<?= htmlspecialchars($formatMoneyInput($target['desired_amount'] ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="Valor desejado" inputmode="decimal" data-money-input data-budget-amount>
                <small class="muted" data-budget-preview>
                    <?= $target['target_percent'] === null ? 'Informe a renda base.' : 'Valor estimado: calcule pela renda base.' ?>
                </small>
            </td>
        </tr>
        <?php
    endforeach;
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bullseye"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Metas por categoria</h1>
            <p class="muted">Configure percentuais de gasto por categoria/DR cruzando sua realidade com referências financeiras.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="report-summary-grid">
    <article class="card report-summary-card">
        <small>Categorias ativas</small>
        <strong><?= count($activeTargets) ?></strong>
        <span class="muted">Base importada da planilha</span>
    </article>
    <article class="card report-summary-card">
        <small>Metas inativas</small>
        <strong><?= count($inactiveTargets) ?></strong>
        <span class="muted">Ficam fora da análise estratégica</span>
    </article>
    <article class="card report-summary-card">
        <small>Como usar</small>
        <strong>% da renda</strong>
        <span class="muted">A meta será comparada com o recebido do período.</span>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-info-circle"></span>Referência usada como apoio</h2>
    <p class="muted">
        As sugestões partem de modelos como 50/30/20, referências de moradia e padrões de consumo familiar.
        Elas servem como dica visual, não como regra fixa. A coluna "Sua meta" deve refletir o seu controle real.
    </p>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-sliders"></span>Configurar metas</h2>
    <form method="post" action="/system/category-budget-targets">
        <div class="soft-panel budget-calculator-panel">
            <div>
                <strong>Calculadora de metas</strong>
                <p class="muted">Informe uma renda base e clique em calcular para preencher valores desejados e percentuais. Depois, use Salvar para armazenar.</p>
            </div>
            <label>
                Renda base para cálculo
                <input type="text" name="budget_base_income" value="<?= htmlspecialchars($formatMoneyInput($baseIncomeValue), ENT_QUOTES, 'UTF-8') ?>" placeholder="R$ 0,00" inputmode="decimal" data-money-input data-budget-base-income data-initial-base-income="<?= htmlspecialchars((string) ($baseIncomeValue ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <button class="inline-button button-light" type="button" data-budget-calculate>
                <span class="bi bi-calculator"></span>Calcular metas
            </button>
        </div>

        <div class="report-summary-grid budget-residual-grid">
            <article class="card report-summary-card">
                <small>Renda base</small>
                <strong data-budget-base-summary>R$ 0,00</strong>
                <span class="muted">Valor usado no cálculo</span>
            </article>
            <article class="card report-summary-card">
                <small>Distribuido</small>
                <strong data-budget-distributed-summary>R$ 0,00</strong>
                <span class="muted">Soma das categorias ativas</span>
            </article>
            <article class="card report-summary-card">
                <small>Residual</small>
                <strong data-budget-residual-summary>R$ 0,00</strong>
                <span class="muted" data-budget-residual-message>Planejamento fechado.</span>
            </article>
        </div>

        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Categoria (DR)</th>
                        <th>Referência sugerida</th>
                        <th>Parâmetro</th>
                        <th>Controle</th>
                        <th>Sua meta</th>
                        <th>Calculadora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $renderRows($activeTargets); ?>
                    <?php if ($activeTargets === []): ?>
                        <tr>
                            <td colspan="6" class="muted">Nenhuma meta ativa encontrada. Ative alguma categoria abaixo ou importe a planilha.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button type="submit" data-budget-save><span class="bi bi-save"></span>Salvar metas por categoria</button>
        </div>

        <details class="card section-card inactive-targets-panel" <?= $inactiveTargets === [] ? '' : 'open' ?>>
            <summary>
                <strong>Categorias inativas</strong>
                <span class="muted"><?= count($inactiveTargets) ?> categoria(s) fora da análise</span>
            </summary>
            <p class="muted">Metas inativas ficam armazenadas, mas não entram na tela Financeiro > Metas de gastos. Reative quando quiser recalcular.</p>
            <div class="responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th>Categoria (DR)</th>
                            <th>Referência sugerida</th>
                            <th>Parâmetro</th>
                            <th>Controle</th>
                            <th>Sua meta</th>
                            <th>Calculadora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $renderRows($inactiveTargets); ?>
                        <?php if ($inactiveTargets === []): ?>
                            <tr>
                                <td colspan="6" class="muted">Nenhuma categoria inativa.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </form>
</section>

<script>
    (() => {
        const baseIncomeInput = document.querySelector('[data-budget-base-income]');
        const calculateButton = document.querySelector('[data-budget-calculate]');
        const saveButton = document.querySelector('[data-budget-save]');
        const baseSummary = document.querySelector('[data-budget-base-summary]');
        const distributedSummary = document.querySelector('[data-budget-distributed-summary]');
        const residualSummary = document.querySelector('[data-budget-residual-summary]');
        const residualMessage = document.querySelector('[data-budget-residual-message]');
        const rows = document.querySelectorAll('[data-budget-percent]');

        const parseNumber = (value) => {
            const clean = String(value || '').replace(/[^\d,.-]+/g, '');

            if (clean === '') {
                return null;
            }

            const normalized = clean.includes(',')
                ? clean.replace(/\./g, '').replace(',', '.')
                : clean.replace(/,/g, '');
            const number = Number(normalized);

            return Number.isFinite(number) ? number : null;
        };
        let lastConfirmedBaseIncome = parseNumber(baseIncomeInput?.dataset.initialBaseIncome);

        const formatPercent = (value) => value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const formatMoney = (value) => value.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });

        const syncPreview = (percentInput) => {
            const row = percentInput.closest('tr');
            const amountInput = row?.querySelector('[data-budget-amount]');
            const preview = row?.querySelector('[data-budget-preview]');
            const baseIncome = parseNumber(baseIncomeInput?.value);
            const percent = parseNumber(percentInput.value);

            if (!preview) {
                return;
            }

            if (!baseIncome || baseIncome <= 0) {
                preview.textContent = 'Informe a renda base.';
                return;
            }

            if (percent === null) {
                preview.textContent = 'Informe percentual ou valor desejado.';
                return;
            }

            const estimatedAmount = baseIncome * (percent / 100);
            preview.textContent = `Valor estimado: ${formatMoney(estimatedAmount)}`;

            if (amountInput && amountInput.value.trim() === '') {
                amountInput.placeholder = formatMoney(estimatedAmount);
            }
        };

        const syncFromAmount = (amountInput) => {
            const row = amountInput.closest('tr');
            const percentInput = row?.querySelector('[data-budget-percent]');
            const baseIncome = parseNumber(baseIncomeInput?.value);
            const amount = parseNumber(amountInput.value);

            if (!percentInput || !baseIncome || baseIncome <= 0 || amount === null) {
                return;
            }

            percentInput.value = formatPercent((amount / baseIncome) * 100);
            syncPreview(percentInput);
            syncResidual();
        };

        const activeRows = () => Array.from(rows).filter((percentInput) => {
            const row = percentInput.closest('tr');
            const activeInput = row?.querySelector('input[name$="[is_active]"][type="checkbox"]');

            return activeInput?.checked ?? true;
        });

        const syncResidual = () => {
            const baseIncome = parseNumber(baseIncomeInput?.value) ?? 0;
            const distributed = activeRows().reduce((carry, percentInput) => {
                const row = percentInput.closest('tr');
                const amountInput = row?.querySelector('[data-budget-amount]');
                const amount = parseNumber(amountInput?.value);

                return carry + (amount ?? 0);
            }, 0);
            const residual = baseIncome - distributed;
            const closed = baseIncome > 0 && Math.abs(residual) <= 0.01;

            if (baseSummary) {
                baseSummary.textContent = formatMoney(baseIncome);
            }

            if (distributedSummary) {
                distributedSummary.textContent = formatMoney(distributed);
            }

            if (residualSummary) {
                residualSummary.textContent = formatMoney(Math.abs(residual));
            }

            if (residualMessage) {
                residualMessage.textContent = closed
                    ? 'Planejamento fechado.'
                    : (residual > 0 ? 'Ainda existe saldo para distribuir.' : 'Metas acima da renda base.');
            }

            if (saveButton) {
                saveButton.disabled = !closed && activeRows().length > 0;
                saveButton.title = saveButton.disabled ? 'Feche o residual antes de salvar.' : '';
            }
        };

        const calculateAllTargets = () => {
            const baseIncome = parseNumber(baseIncomeInput?.value);

            if (!baseIncome || baseIncome <= 0) {
                rows.forEach(syncPreview);
                baseIncomeInput?.focus();
                return;
            }

            rows.forEach((percentInput) => {
                const row = percentInput.closest('tr');
                const amountInput = row?.querySelector('[data-budget-amount]');
                const controlType = row?.querySelector('select[name$="[control_type]"]')?.value;
                const referenceMin = parseNumber(row?.querySelector('input[name$="[reference_min_percent]"]')?.value);
                const referenceMax = parseNumber(row?.querySelector('input[name$="[reference_max_percent]"]')?.value);
                const amount = parseNumber(amountInput?.value);
                const percent = parseNumber(percentInput.value);

                if (amountInput && amount !== null) {
                    percentInput.value = formatPercent((amount / baseIncome) * 100);
                    syncPreview(percentInput);
                    syncResidual();
                    return;
                }

                if (amountInput && percent !== null) {
                    amountInput.value = formatMoney(baseIncome * (percent / 100));
                    syncPreview(percentInput);
                    syncResidual();
                    return;
                }

                const suggestedPercent = controlType === 'minimum'
                    ? (referenceMin ?? referenceMax)
                    : (referenceMax ?? referenceMin);

                if (amountInput && suggestedPercent !== null) {
                    percentInput.value = formatPercent(suggestedPercent);
                    amountInput.value = formatMoney(baseIncome * (suggestedPercent / 100));
                    syncPreview(percentInput);
                    syncResidual();
                    return;
                }

                syncPreview(percentInput);
            });

            syncResidual();
        };

        const confirmBaseIncomeChange = () => {
            const currentBaseIncome = parseNumber(baseIncomeInput?.value);

            if (!currentBaseIncome || currentBaseIncome <= 0 || currentBaseIncome === lastConfirmedBaseIncome) {
                syncResidual();
                return;
            }

            const shouldRecalculate = window.confirm('A renda base foi alterada. Deseja recalcular as metas agora?');
            lastConfirmedBaseIncome = currentBaseIncome;

            if (shouldRecalculate) {
                calculateAllTargets();
                return;
            }

            rows.forEach(syncPreview);
            syncResidual();
        };

        rows.forEach((percentInput) => {
            const row = percentInput.closest('tr');
            const amountInput = row?.querySelector('[data-budget-amount]');
            const activeInput = row?.querySelector('input[name$="[is_active]"][type="checkbox"]');
            const activeLabel = row?.querySelector('.budget-status-label');

            percentInput.addEventListener('input', () => {
                syncPreview(percentInput);
                syncResidual();
            });
            amountInput?.addEventListener('input', () => syncFromAmount(amountInput));
            amountInput?.addEventListener('money:formatted', () => syncFromAmount(amountInput));
            activeInput?.addEventListener('change', () => {
                if (activeLabel) {
                    activeLabel.textContent = activeInput.checked
                        ? (activeLabel.dataset.activeLabel || 'Ativa')
                        : (activeLabel.dataset.inactiveLabel || 'Inativa');
                }

                row?.classList.toggle('is-muted-row', !activeInput.checked);
                syncResidual();
            });
        });

        document.querySelector('form[action="/system/category-budget-targets"]')?.addEventListener('submit', (event) => {
            syncResidual();

            if (saveButton?.disabled) {
                event.preventDefault();
                window.alert('Feche o residual antes de salvar as metas.');
            }
        });

        baseIncomeInput?.addEventListener('input', () => {
            rows.forEach(syncPreview);
            syncResidual();
        });
        baseIncomeInput?.addEventListener('blur', confirmBaseIncomeChange);
        baseIncomeInput?.addEventListener('money:formatted', () => {
            rows.forEach(syncPreview);
            syncResidual();
        });
        calculateButton?.addEventListener('click', calculateAllTargets);
        rows.forEach(syncPreview);
        syncResidual();
    })();
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
