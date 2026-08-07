<?php
$title = 'Dashboard';
ob_start();
?>
<section class="card">
    <span class="badge"><?= htmlspecialchars($user->role->value, ENT_QUOTES, 'UTF-8') ?></span>
    <h1>Ola, <?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="muted">Este painel inicial ja separa autenticacao, autorizacao e a camada que depois vai conversar com sua planilha.</p>

    <div class="actions">
        <?php if ($user->canEdit()): ?>
            <a href="/entries/create"><button class="inline-button" type="button">Novo lancamento</button></a>
        <?php endif; ?>
        <form method="post" action="/logout">
            <button class="inline-button" type="submit">Sair</button>
        </form>
    </div>
</section>

<section class="grid">
    <article class="card">
        <h2>Resumo do mes</h2>
        <p><strong>Referencia:</strong> <?= htmlspecialchars($summary['month'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Receitas:</strong> R$ <?= number_format((float) $summary['income'], 2, ',', '.') ?></p>
        <p><strong>Despesas:</strong> R$ <?= number_format((float) $summary['expenses'], 2, ',', '.') ?></p>
        <p><strong>Saldo:</strong> R$ <?= number_format((float) $summary['balance'], 2, ',', '.') ?></p>
        <p class="muted"><?= htmlspecialchars($summary['status'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>

    <article class="card">
        <h2>Estimativa do proximo mes</h2>
        <p><strong>Receitas:</strong> R$ <?= number_format((float) $upcoming['next_month_estimated_income'], 2, ',', '.') ?></p>
        <p><strong>Despesas:</strong> R$ <?= number_format((float) $upcoming['next_month_estimated_expenses'], 2, ',', '.') ?></p>
        <p class="muted"><?= htmlspecialchars($upcoming['status'], ENT_QUOTES, 'UTF-8') ?></p>
    </article>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');

