<?php
$title = 'Usuarios';
$renderUserForm = static function (array $roles, ?array $managedUser = null): void {
    $isEdit = $managedUser !== null;
    $action = $isEdit ? '/admin/users/update' : '/admin/users';
?>
    <form method="post" action="<?= $action ?>" class="form-grid">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
        <?php endif; ?>
        <label>
            Nome
            <input type="text" name="name" value="<?= htmlspecialchars($managedUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            E-mail
            <input type="email" name="email" value="<?= htmlspecialchars($managedUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            Perfil
            <select name="role" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role->value, ENT_QUOTES, 'UTF-8') ?>" <?= ($managedUser['role'] ?? '') === $role->value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role->label(), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= $isEdit ? 'Nova senha opcional' : 'Senha temporaria' ?>
            <input type="password" name="password" <?= $isEdit ? 'placeholder="Deixe vazio para manter"' : 'required' ?>>
        </label>
        <label>
            Sua senha de administrador
            <input type="password" name="admin_password" required>
        </label>
        <div class="modal-footer-actions">
            <button type="button" class="inline-button button-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit">
                <span class="bi <?= $isEdit ? 'bi-check2-circle' : 'bi-person-plus' ?>"></span>
                <?= $isEdit ? 'Salvar alteracoes' : 'Criar usuario' ?>
            </button>
        </div>
    </form>
<?php
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-people"></span>
        <div>
            <span class="badge">Administrador</span>
            <h1>Usuarios do sistema</h1>
            <p class="muted">Consulte usuarios e abra criacao ou edicao em modal para reduzir ruido visual.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <span class="bi bi-person-plus"></span>Criar usuario
        </button>
        <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_AUDIT_LOGS)): ?>
            <a href="/admin/audit-logs"><button class="inline-button" type="button">Logs de auditoria</button></a>
        <?php endif; ?>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-person-badge"></span>Usuarios cadastrados</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th>Ultimo login</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $managedUser): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($managedUser['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted"><?= htmlspecialchars($managedUser['email'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <span class="badge">
                                <?= htmlspecialchars(\App\Domain\Auth\Role::from($managedUser['role'])->label(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= ((int) $managedUser['is_active']) === 1 ? 'Ativo' : 'Bloqueado' ?></td>
                        <td><?= htmlspecialchars($managedUser['last_login_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="actions">
                                <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal<?= (int) $managedUser['id'] ?>">
                                    <span class="bi bi-pencil-square"></span>Editar
                                </button>
                                <button type="button" class="inline-button <?= ((int) $managedUser['is_active']) === 1 ? 'button-danger' : '' ?>" data-bs-toggle="modal" data-bs-target="#toggleUserModal<?= (int) $managedUser['id'] ?>">
                                    <span class="bi <?= ((int) $managedUser['is_active']) === 1 ? 'bi-lock' : 'bi-unlock' ?>"></span>
                                    <?= ((int) $managedUser['is_active']) === 1 ? 'Bloquear' : 'Desbloquear' ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5"><span class="bi bi-person-plus"></span>Criar usuario</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php $renderUserForm($roles); ?>
            </div>
        </div>
    </div>
</div>

<?php foreach ($users as $managedUser): ?>
    <div class="modal fade" id="editUserModal<?= (int) $managedUser['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5"><span class="bi bi-pencil-square"></span>Editar <?= htmlspecialchars($managedUser['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php $renderUserForm($roles, $managedUser); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="toggleUserModal<?= (int) $managedUser['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="/admin/users/toggle-status">
                    <div class="modal-header">
                        <h2 class="modal-title h5">
                            <span class="bi <?= ((int) $managedUser['is_active']) === 1 ? 'bi-lock' : 'bi-unlock' ?>"></span>
                            <?= ((int) $managedUser['is_active']) === 1 ? 'Bloquear usuario' : 'Desbloquear usuario' ?>
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
                        <input type="hidden" name="active" value="<?= ((int) $managedUser['is_active']) === 1 ? '0' : '1' ?>">
                        <p>Confirme a acao para <strong><?= htmlspecialchars($managedUser['name'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                        <label>
                            Sua senha de administrador
                            <input type="password" name="admin_password" required>
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="inline-button button-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="<?= ((int) $managedUser['is_active']) === 1 ? 'button-danger' : '' ?>">
                            <span class="bi <?= ((int) $managedUser['is_active']) === 1 ? 'bi-lock' : 'bi-unlock' ?>"></span>
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
