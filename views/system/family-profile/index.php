<?php
$title = 'Perfil familiar';
$regions = [
    'brasil' => 'Brasil',
    'norte' => 'Norte',
    'nordeste' => 'Nordeste',
    'sudeste' => 'Sudeste',
    'sul' => 'Sul',
    'centro_oeste' => 'Centro-Oeste',
];
$sexLabels = [
    'female' => 'Feminino',
    'male' => 'Masculino',
    'other' => 'Outro',
];
$age = static function (?string $birthDate): string {
    if ($birthDate === null || $birthDate === '') {
        return '-';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $birthDate);

    return $date instanceof DateTimeImmutable ? $date->diff(new DateTimeImmutable('today'))->format('%y anos') : '-';
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-people"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Perfil familiar</h1>
            <p class="muted">Cadastre a composicao da casa para contextualizar gastos, reserva e metas.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-geo-alt"></span>Referência da casa</h2>
    <form method="post" action="/system/family-profile/settings" class="family-profile-form">
        <label>
            Regiao
            <select name="region">
                <?php foreach ($regions as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($settings['region'] ?? 'brasil') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Area
            <select name="residence_area">
                <option value="urban" <?= ($settings['residence_area'] ?? 'urban') === 'urban' ? 'selected' : '' ?>>Urbana</option>
                <option value="rural" <?= ($settings['residence_area'] ?? 'urban') === 'rural' ? 'selected' : '' ?>>Rural</option>
            </select>
        </label>
        <label>
            Observacao
            <input type="text" name="notes" value="<?= htmlspecialchars((string) ($settings['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar referencia</button>
        </div>
    </form>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-person-plus"></span>Membros</h2>
    <form method="post" action="/system/family-profile/members" class="family-profile-form family-member-form">
        <label>
            Nome
            <input type="text" name="name" required>
        </label>
        <label>
            Sexo
            <select name="sex">
                <?php foreach ($sexLabels as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Nascimento
            <input type="date" name="birth_date">
        </label>
        <label>
            Papel
            <input type="text" name="role" placeholder="Principal renda, conjuge, filho...">
        </label>
        <label>
            Peso na renda (%)
            <input type="number" name="income_weight" min="0" max="100" step="0.01" value="0">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-plus-circle"></span>Adicionar membro</button>
        </div>
    </form>

    <div class="settings-list">
        <?php foreach ($members as $member): ?>
            <details class="settings-item <?= ((int) $member['is_active']) === 1 ? '' : 'is-done' ?>">
                <summary>
                    <strong><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($age($member['birth_date'] ?? null), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($sexLabels[$member['sex']] ?? 'Outro', ENT_QUOTES, 'UTF-8') ?> | <?= ((int) $member['is_active']) === 1 ? 'Ativo' : 'Inativo' ?></small>
                </summary>
                <form method="post" action="/system/family-profile/members" class="form-grid compact-form">
                    <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <select name="sex">
                        <?php foreach ($sexLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($member['sex'] ?? 'other') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars((string) ($member['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="text" name="role" value="<?= htmlspecialchars((string) ($member['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="number" name="income_weight" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($member['income_weight'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar membro</button>
                </form>
                <form method="post" action="/system/family-profile/members/toggle">
                    <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                    <input type="hidden" name="active" value="<?= ((int) $member['is_active']) === 1 ? '0' : '1' ?>">
                    <button class="inline-button <?= ((int) $member['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                        <span class="bi <?= ((int) $member['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                        <?= ((int) $member['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                    </button>
                </form>
            </details>
        <?php endforeach; ?>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-heart"></span>Pets</h2>
    <form method="post" action="/system/family-profile/pets" class="family-profile-form family-pet-form">
        <label>
            Nome
            <input type="text" name="name" required>
        </label>
        <label>
            Tipo
            <input type="text" name="type" placeholder="Cachorro, gato..." required>
        </label>
        <label>
            Nascimento
            <input type="date" name="birth_date">
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-plus-circle"></span>Adicionar pet</button>
        </div>
    </form>

    <div class="settings-list">
        <?php foreach ($pets as $pet): ?>
            <details class="settings-item <?= ((int) $pet['is_active']) === 1 ? '' : 'is-done' ?>">
                <summary>
                    <strong><?= htmlspecialchars((string) $pet['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars((string) $pet['type'], ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($age($pet['birth_date'] ?? null), ENT_QUOTES, 'UTF-8') ?> | <?= ((int) $pet['is_active']) === 1 ? 'Ativo' : 'Inativo' ?></small>
                </summary>
                <form method="post" action="/system/family-profile/pets" class="form-grid compact-form">
                    <input type="hidden" name="id" value="<?= (int) $pet['id'] ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars((string) $pet['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input type="text" name="type" value="<?= htmlspecialchars((string) $pet['type'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars((string) ($pet['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar pet</button>
                </form>
                <form method="post" action="/system/family-profile/pets/toggle">
                    <input type="hidden" name="id" value="<?= (int) $pet['id'] ?>">
                    <input type="hidden" name="active" value="<?= ((int) $pet['is_active']) === 1 ? '0' : '1' ?>">
                    <button class="inline-button <?= ((int) $pet['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                        <span class="bi <?= ((int) $pet['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                        <?= ((int) $pet['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                    </button>
                </form>
            </details>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
