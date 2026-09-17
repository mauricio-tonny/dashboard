<?php
$title = 'Objetivos financeiros';
$formatMoney = static fn (mixed $value): string => format_money_for_user($value, $user ?? null);
$formatInputMoney = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$types = [
    'emergency_reserve' => 'Reserva de emergencia',
    'home_down_payment' => 'Entrada da casa',
    'investment' => 'Investimento',
    'debt_payoff' => 'Quitacao de divida',
    'other' => 'Outro objetivo',
];
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-flag"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Objetivos financeiros</h1>
            <p class="muted">Cadastre metas patrimoniais com prazo para o plano calcular o aporte mensal necessario.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/finance/financial-plan"><button class="inline-button button-light" type="button"><span class="bi bi-compass"></span>Ver plano</button></a>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-plus-circle"></span>Novo objetivo</h2>
    <form method="post" action="/system/financial-goals" class="family-profile-form financial-objective-form">
        <label>
            Nome
            <input type="text" name="name" placeholder="Reserva de 50 mil" required>
        </label>
        <label>
            Tipo
            <select name="goal_type">
                <?php foreach ($types as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Valor inicial
            <input type="text" name="start_amount" value="0,00" inputmode="decimal" data-money-input>
        </label>
        <label>
            Valor atual
            <input type="text" name="current_amount" value="0,00" inputmode="decimal" data-money-input>
        </label>
        <label>
            Valor alvo
            <input type="text" name="target_amount" placeholder="Ex.: 50.000,00" inputmode="decimal" data-money-input required>
        </label>
        <label>
            Data inicial
            <input type="date" name="start_date" value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            Data alvo
            <input type="date" name="target_date" required>
        </label>
        <label>
            Prioridade
            <input type="number" name="priority_weight" min="1" max="10" step="1" value="5">
        </label>
        <label>
            Observacao
            <input type="text" name="notes" placeholder="Ex.: meta principal, manter aporte menor...">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar objetivo</button>
        </div>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-list-check"></span>Objetivos cadastrados</h2>
    <div class="settings-list">
        <?php foreach ($goals as $goal): ?>
            <?php
                $remaining = max(0.0, (float) $goal['target_amount'] - (float) $goal['current_amount']);
                $started = (float) $goal['target_amount'] > 0
                    ? min(100.0, ((float) $goal['current_amount'] / (float) $goal['target_amount']) * 100)
                    : 0.0;
            ?>
            <details class="settings-item <?= ((int) $goal['is_active']) === 1 ? '' : 'is-done' ?>">
                <summary>
                    <strong><?= htmlspecialchars((string) $goal['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small>
                        <?= htmlspecialchars($types[$goal['goal_type']] ?? 'Outro objetivo', ENT_QUOTES, 'UTF-8') ?>
                        | <?= $formatMoney($goal['current_amount']) ?> de <?= $formatMoney($goal['target_amount']) ?>
                        | faltam <?= $formatMoney($remaining) ?>
                        | <?= number_format($started, 1, ',', '.') ?>%
                    </small>
                </summary>
                <form method="post" action="/system/financial-goals" class="form-grid compact-form">
                    <input type="hidden" name="id" value="<?= (int) $goal['id'] ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars((string) $goal['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <select name="goal_type">
                        <?php foreach ($types as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($goal['goal_type'] ?? 'other') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="start_amount" value="<?= htmlspecialchars($formatInputMoney($goal['start_amount']), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" data-money-input>
                    <input type="text" name="current_amount" value="<?= htmlspecialchars($formatInputMoney($goal['current_amount']), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" data-money-input>
                    <input type="text" name="target_amount" value="<?= htmlspecialchars($formatInputMoney($goal['target_amount']), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" data-money-input required>
                    <input type="date" name="start_date" value="<?= htmlspecialchars((string) $goal['start_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input type="date" name="target_date" value="<?= htmlspecialchars((string) $goal['target_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input type="number" name="priority_weight" min="1" max="10" step="1" value="<?= (int) $goal['priority_weight'] ?>">
                    <input type="text" name="notes" value="<?= htmlspecialchars((string) ($goal['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar objetivo</button>
                </form>
                <form method="post" action="/system/financial-goals/toggle">
                    <input type="hidden" name="id" value="<?= (int) $goal['id'] ?>">
                    <input type="hidden" name="active" value="<?= ((int) $goal['is_active']) === 1 ? '0' : '1' ?>">
                    <button class="inline-button <?= ((int) $goal['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                        <span class="bi <?= ((int) $goal['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                        <?= ((int) $goal['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                    </button>
                </form>
            </details>
        <?php endforeach; ?>
        <?php if ($goals === []): ?>
            <p class="muted">Nenhum objetivo cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
