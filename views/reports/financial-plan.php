<?php
$title = 'Plano financeiro';
$formatMoney = static fn (mixed $value): string => format_money_for_user($value, $user ?? null);
$formatPercent = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.') . '%';
$formatMoneyInput = static fn (mixed $value): string => number_format((float) $value, 2, ',', '.');
$balanceClass = ($plan['average_balance_amount'] ?? 0) >= 0 ? 'is-positive' : 'is-negative';
$reserveClass = ($plan['reserve_months'] ?? 0) >= 6 ? 'is-positive' : 'is-negative';
$regionLabels = [
    'brasil' => 'Brasil',
    'norte' => 'Norte',
    'nordeste' => 'Nordeste',
    'sudeste' => 'Sudeste',
    'sul' => 'Sul',
    'centro_oeste' => 'Centro-Oeste',
];
$settings = $familyProfile['settings'] ?? [];
$goalTypeLabels = [
    'emergency_reserve' => 'Reserva de emergencia',
    'home_down_payment' => 'Entrada da casa',
    'investment' => 'Investimento',
    'debt_payoff' => 'Quitacao de divida',
    'other' => 'Outro objetivo',
];
$goalStatusLabels = [
    'reached' => 'Concluido',
    'on_track' => 'No ritmo',
    'needs_adjustment' => 'Ajustar aporte',
    'overdue' => 'Prazo vencido',
];
$formatDate = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '-';
    }

    $value = DateTimeImmutable::createFromFormat('Y-m-d', $date);

    return $value instanceof DateTimeImmutable ? $value->format('d/m/Y') : '-';
};
ob_start();
?>
<section class="page-hero finance-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-compass"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>Plano financeiro</h1>
            <p class="muted">Diagnóstico de <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?> para sair do gargalo e aumentar lastro.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/finance/budget-targets"><button class="inline-button button-light" type="button"><span class="bi bi-arrow-left"></span>Voltar</button></a>
    </div>
</section>

<?php
$filterAction = '/finance/financial-plan';
$showCategoryFilter = false;
$showVendorFilter = false;
require base_path('views/reports/_finance_filters.php');
?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-sliders"></span>Parametros</h2>
    <form method="get" action="/finance/financial-plan" class="financial-goals-form">
        <input type="hidden" name="start_month" value="<?= htmlspecialchars($startMonth, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="end_month" value="<?= htmlspecialchars($endMonth, ENT_QUOTES, 'UTF-8') ?>">
        <label class="goal-field">
            <span class="goal-label">Sobrescrever custo essencial</span>
            <input type="text" name="monthly_need" value="<?= htmlspecialchars($monthlyNeed > 0 ? $formatMoneyInput($monthlyNeed) : '', ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($formatMoney($plan['essential_monthly_need_amount']), ENT_QUOTES, 'UTF-8') ?>" inputmode="decimal" data-money-input>
            <small class="muted goal-help">Vazio usa o cálculo automático.</small>
        </label>
        <div class="form-actions goal-actions">
            <span></span>
            <button type="submit"><span class="bi bi-calculator"></span>Recalcular plano</button>
        </div>
        <div class="form-actions goal-actions">
            <span></span>
            <a href="/system/financial-goals"><button class="button-light" type="button"><span class="bi bi-flag"></span>Gerenciar objetivos</button></a>
            <small class="muted goal-help">Reserva e casa agora vêm do cadastro.</small>
        </div>
    </form>
</section>

<section class="report-summary-grid budget-target-summary-grid">
    <article class="card report-summary-card">
        <small>Saldo médio mensal</small>
        <strong class="<?= htmlspecialchars($balanceClass, ENT_QUOTES, 'UTF-8') ?>"><?= $formatMoney($plan['average_balance_amount']) ?></strong>
        <span class="muted">Receita média <?= $formatMoney($plan['average_income_amount']) ?></span>
    </article>
    <article class="card report-summary-card">
        <small>Saldo sem estimativas</small>
        <strong><?= $formatMoney($plan['average_confirmed_balance_amount']) ?></strong>
        <span class="muted">Estimativas: <?= $formatMoney($plan['average_estimated_expense_amount']) ?>/mês</span>
    </article>
    <article class="card report-summary-card">
        <small>Taxa de sobra</small>
        <strong><?= htmlspecialchars($formatPercent($plan['savings_rate']), ENT_QUOTES, 'UTF-8') ?></strong>
        <span class="muted">Saldo sobre o recebido</span>
    </article>
    <article class="card report-summary-card">
        <small>Reserva</small>
        <strong class="<?= htmlspecialchars($reserveClass, ENT_QUOTES, 'UTF-8') ?>"><?= number_format((float) $plan['reserve_months'], 1, ',', '.') ?> meses</strong>
        <span class="muted">Atual <?= $formatMoney($reserveAmount) ?> | alvo 6 meses <?= $formatMoney($plan['reserve_target_amount']) ?></span>
    </article>
    <article class="card report-summary-card">
        <small>Custo essencial mensal</small>
        <strong><?= $formatMoney($plan['essential_monthly_need_amount']) ?></strong>
        <span class="muted"><?= ($plan['monthly_need_override_amount'] ?? 0) > 0 ? 'Sobrescrito manualmente' : 'Calculado pelo histórico' ?></span>
    </article>
    <article class="card report-summary-card">
        <small>Corte mensal possível</small>
        <strong><?= $formatMoney($plan['monthly_cut_potential_amount']) ?></strong>
        <span class="muted">Baseado nas categorias ajustáveis de maior pressão</span>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-flag"></span>Objetivos cadastrados</h2>
    <?php if (($plan['goal_rows'] ?? []) !== []): ?>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Objetivo</th>
                        <th>Progresso</th>
                        <th>Prazo</th>
                        <th>Aporte necessario</th>
                        <th>Aporte projetado</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plan['goal_rows'] as $goal): ?>
                        <?php $statusClass = in_array($goal['status'], ['reached', 'on_track'], true) ? 'is-positive' : 'is-negative'; ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string) $goal['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <span class="muted"><?= htmlspecialchars($goalTypeLabels[$goal['goal_type']] ?? 'Outro objetivo', ENT_QUOTES, 'UTF-8') ?> | prioridade <?= (int) $goal['priority_weight'] ?></span>
                            </td>
                            <td>
                                <?= $formatMoney($goal['current_amount']) ?> de <?= $formatMoney($goal['target_amount']) ?><br>
                                <span class="muted">Falta <?= $formatMoney($goal['remaining_amount']) ?> | <?= number_format((float) $goal['progress_percent'], 1, ',', '.') ?>%</span>
                            </td>
                            <td>
                                <?= htmlspecialchars($formatDate((string) $goal['target_date']), ENT_QUOTES, 'UTF-8') ?><br>
                                <span class="muted"><?= (int) $goal['months_remaining'] ?> mes(es)</span>
                            </td>
                            <td><?= $formatMoney($goal['required_monthly_amount']) ?>/mês</td>
                            <td>
                                <?= $formatMoney($goal['allocated_monthly_amount']) ?>/mês<br>
                                <?php if (($goal['estimated_date'] ?? null) !== null): ?>
                                    <span class="muted">Conclusão estimada: <?= htmlspecialchars($formatDate((string) $goal['estimated_date']), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <span class="muted">Sem sobra projetada</span>
                                <?php endif; ?>
                            </td>
                            <td class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($goalStatusLabels[$goal['status']] ?? 'Acompanhar', ENT_QUOTES, 'UTF-8') ?><br>
                                <span class="muted">Déficit projetado: <?= $formatMoney($goal['projected_gap_amount']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">Nenhum objetivo ativo cadastrado. Cadastre metas como reserva de emergencia ou entrada da casa para o plano calcular aporte, prazo e deficit.</p>
        <a class="inline-button" href="/system/financial-goals"><span class="bi bi-plus-circle"></span>Cadastrar objetivo</a>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-activity"></span>Diagnóstico</h2>
    <?php if (($plan['average_balance_amount'] ?? 0) < 0): ?>
        <p class="muted">O período analisado indica que a renda não está cobrindo o padrão de saída. A primeira meta é virar o saldo médio para positivo antes de perseguir objetivos maiores.</p>
    <?php elseif (($plan['savings_rate'] ?? 0) < 10): ?>
        <p class="muted">Existe sobra, mas ela ainda está curta para formar patrimônio com velocidade. O foco deve ser aumentar a distância entre renda e despesa sem consumir a reserva.</p>
    <?php else: ?>
        <p class="muted">O período mostra uma base positiva. A prioridade agora é proteger a reserva e direcionar a sobra para uma meta objetiva de entrada da casa.</p>
    <?php endif; ?>
    <p class="muted">Com os cortes sugeridos, a sobra mensal projetada iria para <?= $formatMoney($plan['projected_monthly_balance_amount']) ?>.</p>
    <p class="muted">Fornecedor aqui não é tratado como causa principal, porque cartão concentra compras no banco. A leitura prioriza categoria, descrição, modalidade e parcelas.</p>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-people"></span>Contexto familiar</h2>
    <div class="report-summary-grid budget-target-summary-grid">
        <article class="card report-summary-card">
            <small>Unidade familiar</small>
            <strong><?= (int) ($familyProfile['member_count'] ?? 0) ?> pessoa(s)</strong>
            <span class="muted"><?= (int) ($familyProfile['adult_count'] ?? 0) ?> adulto(s), <?= (int) ($familyProfile['child_count'] ?? 0) ?> criança(s)</span>
        </article>
        <article class="card report-summary-card">
            <small>Pets</small>
            <strong><?= (int) ($familyProfile['pet_count'] ?? 0) ?></strong>
            <span class="muted">Entram como pressão própria quando houver categoria</span>
        </article>
        <article class="card report-summary-card">
            <small>Regua regional</small>
            <strong><?= htmlspecialchars($regionLabels[$settings['region'] ?? 'brasil'] ?? 'Brasil', ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="muted"><?= ($settings['residence_area'] ?? 'urban') === 'rural' ? 'Área rural' : 'Área urbana' ?></span>
        </article>
    </div>
    <p class="muted">Referência baseada na POF/IBGE 2017-2018, ajustada levemente por crianças e pets cadastrados. Use como comparação de pressão, não como meta rígida.</p>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Seu % da renda</th>
                    <th>Referência familiar</th>
                    <th>Diferença</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plan['family_reference_rows'] as $row): ?>
                    <?php $diffClass = (float) $row['difference_percent'] <= 0 ? 'is-positive' : 'is-negative'; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) $row['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($formatPercent($row['actual_percent']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($formatPercent($row['reference_percent']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="<?= htmlspecialchars($diffClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($formatPercent($row['difference_percent']), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-signpost-split"></span>Roteiro</h2>
    <div class="module-card-grid">
        <?php foreach ($plan['roadmap'] as $step): ?>
            <article class="module-card">
                <span class="bi bi-calendar-check"></span>
                <strong><?= htmlspecialchars($step['period'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($step['focus'], ENT_QUOTES, 'UTF-8') ?></strong>
                <p class="muted"><?= htmlspecialchars($step['text'], ENT_QUOTES, 'UTF-8') ?></p>
                <small class="muted">Potencial acumulado: <?= $formatMoney($step['target']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-exclamation-triangle"></span>Causadores de pressão</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Perfil</th>
                    <th>Média mensal</th>
                    <th>% da renda</th>
                    <th>Corte alvo</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plan['pressure_rows'] as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string) $row['profile_label'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $formatMoney($row['monthly_amount']) ?></td>
                        <td><?= htmlspecialchars($formatPercent($row['income_share']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $formatMoney($row['suggested_cut_amount']) ?>/mês</td>
                        <td><span class="muted"><?= htmlspecialchars((string) $row['action_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($plan['pressure_rows'] ?? []) === []): ?>
                    <tr>
                        <td colspan="6" class="muted">Sem despesas no período analisado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-search"></span>Gastos recorrentes</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Categoria</th>
                    <th>Total</th>
                    <th>Média mensal</th>
                    <th>% da renda</th>
                    <th>Lançamentos</th>
                    <th>Leitura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plan['description_pressure_rows'] as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) $row['description'], ENT_QUOTES, 'UTF-8') ?></strong><br><span class="muted"><?= htmlspecialchars((string) ($row['modality_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars((string) $row['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $formatMoney($row['total_amount']) ?></td>
                        <td><?= $formatMoney($row['monthly_amount']) ?></td>
                        <td><?= htmlspecialchars($formatPercent($row['income_share']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= (int) ($row['entries_count'] ?? 0) ?>
                            <?php if (($row['installment_label'] ?? '') !== ''): ?>
                                <br><span class="muted"><?= htmlspecialchars((string) $row['installment_label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="muted"><?= htmlspecialchars((string) $row['action_label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($plan['description_pressure_rows'] ?? []) === []): ?>
                    <tr>
                        <td colspan="6" class="muted">Sem descrições de despesa no período analisado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
