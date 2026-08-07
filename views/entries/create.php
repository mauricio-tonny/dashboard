<?php
$title = 'Novo lancamento';
ob_start();
?>
<section class="card" style="max-width: 720px; margin: 0 auto;">
    <h1>Novo lancamento</h1>
    <p class="muted">Formulario inicial para cadastro. Nesta fase, os dados ficam auditados em log enquanto a integracao real com o Excel ainda nao foi implementada.</p>

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
            <button type="submit">Salvar</button>
            <a href="/"><button class="inline-button" type="button">Cancelar</button></a>
        </div>
    </form>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');

