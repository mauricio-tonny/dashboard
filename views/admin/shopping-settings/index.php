<?php
$title = 'Configuracao Compras';
ob_start();

$renderSimple = static function (string $title, string $kind, array $items): void {
?>
    <article class="card section-card">
        <h2 class="section-title"><span class="bi bi-sliders"></span><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
        <form method="post" action="/admin/shopping-settings/simple" class="inline-form">
            <input type="hidden" name="kind" value="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>">
            <label>
                Nome
                <input type="text" name="name" required>
            </label>
            <button class="inline-button" type="submit"><span class="bi bi-plus-circle"></span>Adicionar</button>
        </form>

        <div class="settings-list">
            <?php foreach ($items as $item): ?>
                <details class="settings-item <?= ((int) $item['is_active']) === 1 ? '' : 'is-done' ?>">
                    <summary>
                        <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= ((int) $item['is_active']) === 1 ? 'Ativo' : 'Inativo' ?></small>
                    </summary>
                    <form method="post" action="/admin/shopping-settings/simple" class="compact-form">
                        <input type="hidden" name="kind" value="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <button type="submit"><span class="bi bi-check2-circle"></span>Salvar</button>
                    </form>
                    <form method="post" action="/admin/shopping-settings/simple/toggle">
                        <input type="hidden" name="kind" value="<?= htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="active" value="<?= ((int) $item['is_active']) === 1 ? '0' : '1' ?>">
                        <button class="inline-button <?= ((int) $item['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                            <span class="bi <?= ((int) $item['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                            <?= ((int) $item['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                        </button>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </article>
<?php
};
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-sliders"></span>
        <div>
            <span class="badge">Administrador</span>
            <h1>Configuracao compras</h1>
            <p class="muted">Gerencie os cadastros usados nas listas de casa, familia e veiculos.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <a href="/shopping"><button class="inline-button" type="button">Voltar para compras</button></a>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="grid">
    <?php $renderSimple('Comodos', 'rooms', $rooms); ?>
    <?php $renderSimple('Para quem', 'people', $people); ?>
    <?php $renderSimple('Areas do veiculo', 'vehicle_areas', $vehicleAreas); ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-car-front"></span>Veiculos</h2>
    <form method="post" action="/admin/shopping-settings/vehicles" class="form-grid">
        <label>
            Nome
            <input type="text" name="name" required>
        </label>
        <label>
            Modelo
            <input type="text" name="model">
        </label>
        <label>
            Marca
            <select name="brand">
                <option value="">Selecione</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Ano modelo
            <input type="number" name="model_year" min="1900" max="2100">
        </label>
        <label>
            Ano fabricacao
            <input type="number" name="manufacture_year" min="1900" max="2100">
        </label>
        <label>
            Renavam
            <input type="text" name="renavam">
        </label>
        <label>
            Placa
            <input type="text" name="plate">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-plus-circle"></span>Adicionar veiculo</button>
        </div>
    </form>

    <div class="settings-list">
        <?php foreach ($vehicles as $vehicle): ?>
            <details class="settings-item <?= ((int) $vehicle['is_active']) === 1 ? '' : 'is-done' ?>">
                <summary>
                    <strong><?= htmlspecialchars($vehicle['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small>
                        <?= htmlspecialchars($vehicle['brand'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($vehicle['model'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        | <?= ((int) $vehicle['is_active']) === 1 ? 'Ativo' : 'Inativo' ?>
                    </small>
                </summary>
                <form method="post" action="/admin/shopping-settings/vehicles" class="form-grid compact-form">
                    <input type="hidden" name="id" value="<?= (int) $vehicle['id'] ?>">
                    <label>
                        Nome
                        <input type="text" name="name" value="<?= htmlspecialchars($vehicle['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label>
                        Modelo
                        <input type="text" name="model" value="<?= htmlspecialchars($vehicle['model'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        Marca
                        <select name="brand">
                            <option value="">Selecione</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>" <?= ($vehicle['brand'] ?? '') === $brand ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Ano modelo
                        <input type="number" name="model_year" value="<?= htmlspecialchars((string) ($vehicle['model_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" min="1900" max="2100">
                    </label>
                    <label>
                        Ano fabricacao
                        <input type="number" name="manufacture_year" value="<?= htmlspecialchars((string) ($vehicle['manufacture_year'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" min="1900" max="2100">
                    </label>
                    <label>
                        Renavam
                        <input type="text" name="renavam" value="<?= htmlspecialchars($vehicle['renavam'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        Placa
                        <input type="text" name="plate" value="<?= htmlspecialchars($vehicle['plate'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar veiculo</button>
                </form>
                <form method="post" action="/admin/shopping-settings/vehicles/toggle">
                    <input type="hidden" name="id" value="<?= (int) $vehicle['id'] ?>">
                    <input type="hidden" name="active" value="<?= ((int) $vehicle['is_active']) === 1 ? '0' : '1' ?>">
                    <button class="inline-button <?= ((int) $vehicle['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                        <span class="bi <?= ((int) $vehicle['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                        <?= ((int) $vehicle['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                    </button>
                </form>
            </details>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
