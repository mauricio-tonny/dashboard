<?php
$formatMoney = static fn ($value): string => $value === null ? '-' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatMoneyInput = static fn ($value): string => $value === null || $value === '' ? '' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDate = static function (?string $value): string {
    if ($value === null || trim($value) === '') {
        return '-';
    }

    return (new DateTimeImmutable($value))->format('d/m/Y');
};
$todayInput = (new DateTimeImmutable('now'))->format('Y-m-d');
$itemsByVehicle = [];

if ($type === 'vehicle') {
    foreach ($items as $item) {
        $vehicleName = (string) ($item['vehicle_name'] ?? 'Sem veiculo');
        $itemsByVehicle[$vehicleName][] = $item;
    }
}

$renderForm = static function (
    string $action,
    string $type,
    string $optionLabel,
    string $optionField,
    array $options,
    array $vehicleAreas,
    bool $hasPriority,
    ?array $item = null
) use ($formatMoneyInput): void {
    ?>
    <form method="post" action="<?= $action ?>" class="form-grid wish-form-grid">
        <?php if ($item !== null): ?>
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
        <?php endif; ?>
        <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
        <label>
            Nome do item
            <input type="text" name="name" value="<?= htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            <?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>
            <select name="<?= htmlspecialchars($optionField, ENT_QUOTES, 'UTF-8') ?>" required>
                <option value="">Selecione</option>
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
                    <option value="">Selecione</option>
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
            <input type="text" name="estimated_amount" value="<?= htmlspecialchars($formatMoneyInput($item['estimated_amount'] ?? null), ENT_QUOTES, 'UTF-8') ?>" placeholder="R$ 0,00" inputmode="decimal" data-money-input>
        </label>
        <?php if ($hasPriority): ?>
            <label>
                Prioridade
                <select name="priority">
                    <?php for ($priority = 0; $priority <= 10; $priority++): ?>
                        <option value="<?= $priority ?>" <?= (int) ($item['priority'] ?? 0) === $priority ? 'selected' : '' ?>><?= $priority ?></option>
                    <?php endfor; ?>
                </select>
            </label>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit"><span class="bi <?= $item === null ? 'bi-plus-circle' : 'bi-check2-circle' ?>"></span><?= $item === null ? 'Adicionar' : 'Salvar' ?></button>
        </div>
    </form>
    <?php
};

$renderItem = static function (array $item) use (
    $type,
    $formatMoney,
    $formatDate,
    $optionLabel,
    $optionField,
    $options,
    $vehicleAreas,
    $hasPriority,
    $renderForm,
    $todayInput
): void {
    $isPurchased = ((int) $item['is_purchased']) === 1;
    ?>
    <div class="shopping-item-card <?= $isPurchased ? 'is-done' : '' ?>">
        <div class="shopping-item-main">
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
                <?php if ($isPurchased): ?>
                    | Comprado em <?= htmlspecialchars($formatDate($item['purchased_at'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </small>
        </div>
        <div class="actions shopping-item-actions">
            <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#editWishItemModal<?= (int) $item['id'] ?>">
                <span class="bi bi-pencil-square"></span>Editar
            </button>
            <?php if ($type === 'vehicle' && !$isPurchased): ?>
                <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#purchaseWishItemModal<?= (int) $item['id'] ?>">
                    <span class="bi bi-check2-square"></span>Comprado
                </button>
            <?php else: ?>
                <form method="post" action="/shopping/wish-items/toggle">
                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="purchased" value="<?= $isPurchased ? '0' : '1' ?>">
                    <button class="inline-button" type="submit">
                        <span class="bi <?= $isPurchased ? 'bi-arrow-counterclockwise' : 'bi-check2-square' ?>"></span>
                        <?= $isPurchased ? 'Reabrir' : 'Comprado' ?>
                    </button>
                </form>
            <?php endif; ?>
            <form method="post" action="/shopping/wish-items/delete">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                <button class="inline-button button-danger" type="submit"><span class="bi bi-trash3"></span>Remover</button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editWishItemModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5"><span class="bi bi-pencil-square"></span>Editar <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php $renderForm('/shopping/wish-items/update', $type, $optionLabel, $optionField, $options, $vehicleAreas, $hasPriority, $item); ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($type === 'vehicle' && !$isPurchased): ?>
        <div class="modal fade" id="purchaseWishItemModal<?= (int) $item['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="/shopping/wish-items/toggle">
                        <div class="modal-header">
                            <h2 class="modal-title h5"><span class="bi bi-check2-square"></span>Confirmar compra</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="type" value="vehicle">
                            <input type="hidden" name="purchased" value="1">
                            <p class="muted">Informe quando o item foi comprado para manter o historico correto.</p>
                            <label>
                                Data da compra
                                <input type="date" name="purchased_at" value="<?= htmlspecialchars($todayInput, ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="inline-button button-light" data-bs-dismiss="modal">Cancelar</button>
                            <button class="inline-button" type="submit"><span class="bi bi-check2-square"></span>Comprado</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php
};

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

<section class="card section-card shopping-panel wish-create-panel">
    <h2 class="section-title"><span class="bi bi-plus-circle"></span>Novo item</h2>
    <?php $renderForm('/shopping/wish-items', $type, $optionLabel, $optionField, $options, $vehicleAreas, $hasPriority); ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-check2-square"></span>Itens cadastrados</h2>
    <?php if ($type === 'vehicle'): ?>
        <div class="shopping-list vehicle-group-list">
            <?php foreach ($itemsByVehicle as $vehicleName => $vehicleItems): ?>
                <section class="vehicle-group">
                    <h3><span class="bi bi-car-front"></span><?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?></h3>
                    <div class="shopping-list">
                        <?php foreach ($vehicleItems as $item): ?>
                            <?php $renderItem($item); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="shopping-list">
            <?php foreach ($items as $item): ?>
                <?php $renderItem($item); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($items === []): ?>
        <p class="muted">Nenhum item cadastrado ainda.</p>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
