<?php
$title = 'Novo lancamento';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-plus-circle"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>Novo lancamento</h1>
            <p class="muted">Formulario inicial para cadastro enquanto a integração real com o Excel ainda não foi implementada.</p>
        </div>
    </div>
</section>

<section class="card section-card" style="max-width: 820px;">
    <h2 class="section-title"><span class="bi bi-pencil-square"></span>Dados do lancamento</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/entries">
        <label>
            Data
            <input type="date" name="date" required>
        </label>

        <label>
            Descricao
            <input type="text" name="description" required>
        </label>

        <label>
            Categoria
            <input type="text" name="category" required>
        </label>

        <label>
            Fornecedor
            <input type="text" name="vendor">
        </label>

        <label>
            Tipo
            <select name="type" required>
                <option value="expense">Despesa</option>
                <option value="income">Receita</option>
            </select>
        </label>

        <label>
            Valor
            <input type="number" step="0.01" name="amount" required>
        </label>

        <div class="actions">
            <button type="submit"><span class="bi bi-check2-circle"></span>Salvar</button>
            <a href="/finance/payable"><button class="inline-button" type="button">Cancelar</button></a>
        </div>
    </form>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
