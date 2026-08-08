<?php
$formatMoney = static fn ($value): string => $value === null ? '-' : 'R$ ' . number_format((float) $value, 2, ',', '.');
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi <?= $type === 'home' ? 'bi-house-heart' : ($type === 'family' ? 'bi-people' : 'bi-car-front') ?>"></span>
        <div>
            <span class="badge">Compras</span>
            <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="muted">Lista separada para manter o modulo de compras mais organizado e facil de usar.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card shopping-panel">
    <h2 class="section-title"><span class="bi bi-plus-circle"></span>Novo item</h2>
    <form method="post" action="/shopping/wish-items" class="form-grid">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
        <label>
            Nome do item
            <input type="text" name="name" required>
        </label>
        <label>
            <?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>
            <select name="<?= htmlspecialchars($optionField, ENT_QUOTES, 'UTF-8') ?>" required>
                <option value="">Selecione</option>
                <?php foreach ($options as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"><?= htmlspecialchars($option['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if ($type === 'vehicle'): ?>
            <label>
                Area
                <select name="vehicle_area_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($vehicleAreas as $area): ?>
                        <option value="<?= (int) $area['id'] ?>"><?= htmlspecialchars($area['name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>
            Valor previsto
            <input type="text" name="estimated_amount" placeholder="0,00">
        </label>
        <?php if ($hasPriority): ?>
            <label>
                Prioridade
                <input type="number" name="priority" min="0" max="10" value="0">
            </label>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-plus-circle"></span>Adicionar</button>
        </div>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-check2-square"></span>Itens cadastrados</h2>
    <div class="shopping-list">
        <?php foreach ($items as $item): ?>
            <details class="shopping-item <?= ((int) $item['is_purchased']) === 1 ? 'is-done' : '' ?>">
                <summary>
                    <span>
                        <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small>
                            <?= htmlspecialchars($item['room_name'] ?? $item['person_name'] ?? $item['vehicle_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($type === 'vehicle'): ?>
                                | <?= htmlspecialchars($item['vehicle_area_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                            | <?= htmlspecialchars($formatMoney($item['estimated_amount']), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($hasPriority): ?>
                                | Prioridade <?= (int) ($item['priority'] ?? 0) ?>
                            <?php endif; ?>
                        </small>
                    </span>
                </summary>

                <form method="post" action="/shopping/wish-items/update" class="form-grid compact-form">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                    <label>
                        Nome
                        <input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        <?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>
                        <select name="<?= htmlspecialchars($optionField, ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php foreach ($options as $option): ?>
                                <option value="<?= (int) $option['id'] ?>" <?= (int) ($item[$optionField] ?? 0) === (int) $option['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($option['name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if ($type === 'vehicle'): ?>
                        <label>
                            Area
                            <select name="vehicle_area_id" required>
                                <?php foreach ($vehicleAreas as $area): ?>
                                    <option value="<?= (int) $area['id'] ?>" <?= (int) ($item['vehicle_area_id'] ?? 0) === (int) $area['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                    <label>
                        Valor previsto
                        <input type="text" name="estimated_amount" value="<?= htmlspecialchars((string) ($item['estimated_amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <?php if ($hasPriority): ?>
                        <label>
                            Prioridade
                            <input type="number" name="priority" min="0" max="10" value="<?= (int) ($item['priority'] ?? 0) ?>">
                        </label>
                    <?php endif; ?>
                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar</button>
                </form>

                <div class="actions">
                    <form method="post" action="/shopping/wish-items/toggle">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="purchased" value="<?= ((int) $item['is_purchased']) === 1 ? '0' : '1' ?>">
                        <button class="inline-button" type="submit">
                            <span class="bi <?= ((int) $item['is_purchased']) === 1 ? 'bi-arrow-counterclockwise' : 'bi-check2-square' ?>"></span>
                            <?= ((int) $item['is_purchased']) === 1 ? 'Reabrir' : 'Marcar comprado' ?>
                        </button>
                    </form>
                    <form method="post" action="/shopping/wish-items/delete">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="inline-button button-danger" type="submit"><span class="bi bi-trash3"></span>Remover</button>
                    </form>
                </div>
            </details>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <p class="muted">Nenhum item cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
