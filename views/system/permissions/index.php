<?php
$title = 'Permissoes';
$roleLabel = static fn (string $role): string => \App\Domain\Auth\Role::from($role)->label();
$effectLabel = static fn (?string $effect, bool $baseAllowed): string => match ($effect) {
    'allow' => 'Permitido manualmente',
    'deny' => 'Bloqueado manualmente',
    default => $baseAllowed ? 'Herdando: permitido' : 'Herdando: bloqueado',
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-shield-lock"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Permissoes por usuario</h1>
            <p class="muted">Ajuste excecoes individuais sem alterar o perfil base do usuario.</p>
        </div>
    </div>
    <div class="hero-actions">
        <a href="/admin/users"><button class="inline-button button-light" type="button"><span class="bi bi-people"></span>Usuarios</button></a>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-person-check"></span>Usuario em configuracao</h2>
    <form method="get" action="/system/permissions" class="finance-filter-form">
        <label>
            Usuario
            <select name="user_id">
                <?php foreach ($users as $managedUser): ?>
                    <option value="<?= (int) $managedUser['id'] ?>" <?= (int) $managedUser['id'] === (int) $selectedUserId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $managedUser['name'], ENT_QUOTES, 'UTF-8') ?>
                        (<?= htmlspecialchars($roleLabel((string) $managedUser['role']), ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-search"></span>Carregar</button>
        </div>
    </form>
</section>

<?php if ($selectedUser !== null): ?>
    <section class="card section-card">
        <h2 class="section-title"><span class="bi bi-sliders"></span>Matriz de permissoes</h2>
        <p class="muted">
            Perfil base:
            <strong><?= htmlspecialchars($roleLabel((string) $selectedUser['role']), ENT_QUOTES, 'UTF-8') ?></strong>.
            Use bloqueio manual para remover acessos especificos, como relatorios financeiros.
        </p>
        <form method="post" action="/system/permissions" class="permission-matrix-form">
            <input type="hidden" name="user_id" value="<?= (int) $selectedUserId ?>">
            <div class="permission-grid">
                <?php foreach ($permissions as $permission): ?>
                    <?php
                    $name = (string) $permission['name'];
                    $effect = $overrides[$name] ?? null;
                    $baseAllowed = in_array($name, $rolePermissions, true);
                    ?>
                    <article class="permission-card">
                        <div>
                            <strong><?= htmlspecialchars((string) $permission['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <p class="muted"><?= htmlspecialchars((string) ($permission['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <span class="permission-state <?= $effect === 'deny' ? 'is-deny' : ($effect === 'allow' ? 'is-allow' : '') ?>">
                                <?= htmlspecialchars($effectLabel($effect, $baseAllowed), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <div class="permission-options">
                            <label>
                                <input type="radio" name="effects[<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>]" value="" <?= $effect === null ? 'checked' : '' ?>>
                                Herdar
                            </label>
                            <label>
                                <input type="radio" name="effects[<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>]" value="allow" <?= $effect === 'allow' ? 'checked' : '' ?>>
                                Permitir
                            </label>
                            <label>
                                <input type="radio" name="effects[<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>]" value="deny" <?= $effect === 'deny' ? 'checked' : '' ?>>
                                Bloquear
                            </label>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="permission-save-bar">
                <label>
                    Sua senha de administrador
                    <input type="password" name="admin_password" required>
                </label>
                <button type="submit"><span class="bi bi-shield-check"></span>Salvar permissoes</button>
            </div>
        </form>
    </section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
