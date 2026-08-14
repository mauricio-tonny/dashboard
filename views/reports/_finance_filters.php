<?php
$selectedCategoryCount = count($categoryIds);
$selectedVendorCount = count($vendorIds);
$action = $filterAction ?? '/reports';
$showCategoryFilter = $showCategoryFilter ?? true;
$showVendorFilter = $showVendorFilter ?? true;
?>
<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-funnel"></span>Filtros</h2>
    <form method="get" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="finance-filter-form">
        <label>
            De
            <input type="month" name="start_month" value="<?= htmlspecialchars($startMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
            Ate
            <input type="month" name="end_month" value="<?= htmlspecialchars($endMonth, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-search"></span>Aplicar filtros</button>
        </div>
        <?php if ($showCategoryFilter): ?>
            <details class="category-multi-filter" <?= $selectedCategoryCount > 0 ? 'open' : '' ?>>
                <summary>
                    <span>Categorias</span>
                    <small><?= $selectedCategoryCount === 0 ? 'Todas' : $selectedCategoryCount . ' selecionada(s)' ?></small>
                </summary>
                <p class="muted">Use categorias para refinar os relatorios de despesas.</p>
                <div class="category-chip-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php $id = (int) $category['id']; ?>
                        <label class="category-filter-chip">
                            <input type="checkbox" name="category_ids[]" value="<?= $id ?>" <?= in_array($id, $categoryIds, true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
        <?php if ($showVendorFilter): ?>
            <details class="category-multi-filter" <?= $selectedVendorCount > 0 ? 'open' : '' ?>>
                <summary>
                    <span>Fornecedores</span>
                    <small><?= $selectedVendorCount === 0 ? 'Todos' : $selectedVendorCount . ' selecionado(s)' ?></small>
                </summary>
                <p class="muted">Use fornecedores para focar em gastos de contatos especificos.</p>
                <div class="category-chip-grid">
                    <?php foreach ($vendors as $vendor): ?>
                        <?php $id = (int) $vendor['id']; ?>
                        <label class="category-filter-chip">
                            <input type="checkbox" name="vendor_ids[]" value="<?= $id ?>" <?= in_array($id, $vendorIds, true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars((string) $vendor['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
    </form>
</section>
