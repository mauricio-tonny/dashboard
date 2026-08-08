<?php
$title = 'Compras';
ob_start();

$formatMoney = static fn ($value): string => $value === null ? '-' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$monthLabel = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '-';
    }

    return (new DateTimeImmutable($date))->format('m/Y');
};
$initial = static fn (string $name): string => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
$selectedListId = $selectedMarketList === null ? 0 : (int) $selectedMarketList['id'];

$renderWishSection = static function (
    string $title,
    string $type,
    array $items,
    array $options,
    string $optionField,
    string $optionLabel,
    bool $hasPriority = false,
    array $vehicleAreas = []
) use ($formatMoney): void {
?>
    <article class="card shopping-panel">
        <h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
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
                <button type="submit">Adicionar</button>
            </div>
        </form>

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
                        <button type="submit">Salvar</button>
                    </form>

                    <div class="actions">
                        <form method="post" action="/shopping/wish-items/toggle">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="purchased" value="<?= ((int) $item['is_purchased']) === 1 ? '0' : '1' ?>">
                            <button class="inline-button" type="submit"><?= ((int) $item['is_purchased']) === 1 ? 'Reabrir' : 'Marcar comprado' ?></button>
                        </form>
                        <form method="post" action="/shopping/wish-items/delete">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="inline-button button-danger" type="submit">Remover</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
            <?php if ($items === []): ?>
                <p class="muted">Nenhum item cadastrado ainda.</p>
            <?php endif; ?>
        </div>
    </article>
<?php
};
?>
<section class="card">
    <span class="badge">compras</span>
    <h1>Listas de compras</h1>
    <p class="muted">Controle mercado por mes e mantenha listas unicas para casa, familia e veiculos.</p>

    <div class="actions">
        <a href="/"><button class="inline-button" type="button">Voltar ao dashboard</button></a>
        <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SHOPPING_SETTINGS)): ?>
            <a href="/admin/shopping-settings"><button class="inline-button" type="button">Configuracao compras</button></a>
        <?php endif; ?>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card shopping-hero">
    <div>
        <h2>Mercado mensal</h2>
        <p class="muted">A sugestao padrao e sempre o proximo mes.</p>
    </div>
    <form method="post" action="/shopping/market/lists" class="inline-form">
        <label>
            Mes da lista
            <input type="month" name="reference_month" value="<?= htmlspecialchars(substr($nextMonth, 0, 7), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <button class="inline-button" type="submit">Criar/selecionar lista</button>
    </form>
</section>

<section class="grid">
    <article class="card">
        <h2>Listas de mercado</h2>
        <?php foreach ($marketLists as $list): ?>
            <a class="month-pill <?= (int) $list['id'] === $selectedListId ? 'is-active' : '' ?>" href="/shopping?market_list_id=<?= (int) $list['id'] ?>">
                <?= htmlspecialchars($monthLabel($list['reference_month']), ENT_QUOTES, 'UTF-8') ?>
                <small><?= (int) $list['checked_count'] ?>/<?= (int) $list['item_count'] ?> itens</small>
            </a>
        <?php endforeach; ?>
        <?php if ($marketLists === []): ?>
            <p class="muted">Crie a primeira lista para comecar.</p>
        <?php endif; ?>
    </article>

    <article class="card">
        <h2>Itens do mercado</h2>
        <?php if ($selectedMarketList): ?>
            <p class="muted">
                Lista de <?= htmlspecialchars($monthLabel($selectedMarketList['reference_month']), ENT_QUOTES, 'UTF-8') ?>.
                Total: <?= htmlspecialchars($formatMoney($selectedMarketList['total_amount']), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <form method="post" action="/shopping/market/items" class="form-grid">
                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                <label>
                    Nome do item
                    <input type="text" name="name" required>
                </label>
                <label>
                    Sessao
                    <input type="text" name="section" placeholder="Hortifruti, limpeza, carnes..." required>
                </label>
                <div class="form-actions">
                    <button type="submit">Adicionar item</button>
                </div>
            </form>

            <div class="market-checklist">
                <?php foreach ($marketItems as $item): ?>
                    <div class="market-item <?= ((int) $item['is_checked']) === 1 ? 'is-done' : '' ?>">
                        <div class="item-photo"><?= htmlspecialchars($initial($item['name']), ENT_QUOTES, 'UTF-8') ?></div>
                        <form method="post" action="/shopping/market/items/toggle" class="check-form">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                            <input type="hidden" name="checked" value="<?= ((int) $item['is_checked']) === 1 ? '0' : '1' ?>">
                            <button class="check-button" type="submit"><?= ((int) $item['is_checked']) === 1 ? 'OK' : 'Pegar' ?></button>
                        </form>
                        <div class="market-item-copy">
                            <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small><?= htmlspecialchars($item['section'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <details>
                            <summary>Editar</summary>
                            <form method="post" action="/shopping/market/items/update" class="compact-form">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <input type="text" name="section" value="<?= htmlspecialchars($item['section'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <button type="submit">Salvar</button>
                            </form>
                            <form method="post" action="/shopping/market/items/delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                                <button class="button-danger" type="submit">Remover</button>
                            </form>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="/shopping/market/lists/finish" class="inline-form total-form">
                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                <label>
                    Valor total da compra
                    <input type="text" name="total_amount" placeholder="0,00" value="<?= htmlspecialchars((string) ($selectedMarketList['total_amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <button class="inline-button" type="submit">Finalizar lista</button>
            </form>
        <?php else: ?>
            <p class="muted">Selecione ou crie uma lista mensal.</p>
        <?php endif; ?>
    </article>
</section>

<section class="shopping-columns">
    <?php $renderWishSection('Para casa', 'home', $homeItems, $rooms, 'room_id', 'Comodo', true); ?>
    <?php $renderWishSection('Para a familia', 'family', $familyItems, $people, 'person_id', 'Para quem'); ?>
    <?php $renderWishSection('Para o veiculo', 'vehicle', $vehicleItems, $vehicles, 'vehicle_id', 'Para qual veiculo', false, $vehicleAreas); ?>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
