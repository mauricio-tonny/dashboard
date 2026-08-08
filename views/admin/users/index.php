<?php
$title = 'Usuarios';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-people"></span>
        <div>
            <span class="badge">Administrador</span>
            <h1>Usuarios do sistema</h1>
            <p class="muted">Crie, edite e bloqueie acessos. Alteracoes exigem sua senha para reduzir riscos.</p>
        </div>
    </div>

    <div class="page-hero-actions">
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
    <h2 class="section-title"><span class="bi bi-person-plus"></span>Novo usuario</h2>
    <form method="post" action="/admin/users" class="form-grid">
        <label>
            Nome
            <input type="text" name="name" required>
        </label>
        <label>
            E-mail
            <input type="email" name="email" required>
        </label>
        <label>
            Perfil
            <select name="role" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role->value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($role->label(), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Senha temporaria
            <input type="password" name="password" required>
        </label>
        <label>
            Sua senha de administrador
            <input type="password" name="admin_password" required>
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-person-plus"></span>Criar usuario</button>
        </div>
    </form>
</section>

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
                            <details class="user-details">
                                <summary><span class="bi bi-pencil-square"></span>Editar</summary>
                                <form method="post" action="/admin/users/update" class="stacked-form">
                                    <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
                                    <label>
                                        Nome
                                        <input type="text" name="name" value="<?= htmlspecialchars($managedUser['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    </label>
                                    <label>
                                        E-mail
                                        <input type="email" name="email" value="<?= htmlspecialchars($managedUser['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    </label>
                                    <label>
                                        Perfil
                                        <select name="role" required>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= htmlspecialchars($role->value, ENT_QUOTES, 'UTF-8') ?>" <?= $managedUser['role'] === $role->value ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role->label(), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>
                                        Nova senha opcional
                                        <input type="password" name="password" placeholder="Deixe vazio para manter">
                                    </label>
                                    <label>
                                        Sua senha de administrador
                                        <input type="password" name="admin_password" required>
                                    </label>
                                    <button type="submit"><span class="bi bi-check2-circle"></span>Salvar alteracoes</button>
                                </form>

                                <form method="post" action="/admin/users/toggle-status" class="stacked-form">
                                    <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
                                    <input type="hidden" name="active" value="<?= ((int) $managedUser['is_active']) === 1 ? '0' : '1' ?>">
                                    <label>
                                        Sua senha de administrador
                                        <input type="password" name="admin_password" required>
                                    </label>
                                    <button type="submit" class="<?= ((int) $managedUser['is_active']) === 1 ? 'button-danger' : '' ?>">
                                        <span class="bi <?= ((int) $managedUser['is_active']) === 1 ? 'bi-lock' : 'bi-unlock' ?>"></span>
                                        <?= ((int) $managedUser['is_active']) === 1 ? 'Bloquear usuario' : 'Desbloquear usuario' ?>
                                    </button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
