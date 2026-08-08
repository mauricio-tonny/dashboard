<?php
$title = 'Contatos';
$typeLabel = static fn (string $type): string => $type === 'vendor' ? 'Fornecedor' : 'Cliente';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-person-lines-fill"></span>
        <div>
            <span class="badge">Contatos</span>
            <h1>Fornecedores e clientes</h1>
            <p class="muted">Cadastre contatos que depois poderao ser vinculados aos lancamentos financeiros.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <a href="/contacts"><button class="inline-button" type="button">Todos</button></a>
        <a href="/contacts?type=vendor"><button class="inline-button" type="button">Fornecedores</button></a>
        <a href="/contacts?type=client"><button class="inline-button" type="button">Clientes</button></a>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
    <section class="card section-card">
        <h2 class="section-title"><span class="bi bi-person-plus"></span>Novo contato</h2>
        <form method="post" action="/contacts" class="form-grid">
            <label>
                Tipo
                <select name="type" required>
                    <option value="vendor">Fornecedor</option>
                    <option value="client">Cliente</option>
                </select>
            </label>
            <label>
                Nome
                <input type="text" name="first_name" required>
            </label>
            <label>
                Sobrenome
                <input type="text" name="last_name">
            </label>
            <label>
                CPF/CNPJ
                <input type="text" name="document">
            </label>
            <label>
                Telefone
                <input type="text" name="phone">
            </label>
            <label>
                E-mail
                <input type="email" name="email">
            </label>
            <label>
                Endereco
                <input type="text" name="address">
            </label>
            <label>
                Cidade
                <input type="text" name="city" required>
            </label>
            <label>
                UF
                <select name="state" required>
                    <?php foreach ($states as $state): ?>
                        <option value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-actions">
                <button type="submit"><span class="bi bi-person-plus"></span>Salvar contato</button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-list"></span>Contatos cadastrados</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Contato</th>
                    <th>Cidade/UF</th>
                    <th>Status</th>
                    <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
                        <th>Acoes</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($contact['first_name'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted"><?= htmlspecialchars($contact['document'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($typeLabel($contact['type']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?= htmlspecialchars($contact['phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="muted"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($contact['city'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($contact['state'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ((int) $contact['is_active']) === 1 ? 'Ativo' : 'Inativo' ?></td>
                        <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
                            <td>
                                <details class="user-details">
                                    <summary><span class="bi bi-pencil-square"></span>Editar</summary>
                                    <form method="post" action="/contacts/update" class="form-grid compact-form">
                                        <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                                        <label>
                                            Tipo
                                            <select name="type" required>
                                                <option value="vendor" <?= $contact['type'] === 'vendor' ? 'selected' : '' ?>>Fornecedor</option>
                                                <option value="client" <?= $contact['type'] === 'client' ? 'selected' : '' ?>>Cliente</option>
                                            </select>
                                        </label>
                                        <label>
                                            Nome
                                            <input type="text" name="first_name" value="<?= htmlspecialchars($contact['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </label>
                                        <label>
                                            Sobrenome
                                            <input type="text" name="last_name" value="<?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </label>
                                        <label>
                                            CPF/CNPJ
                                            <input type="text" name="document" value="<?= htmlspecialchars($contact['document'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </label>
                                        <label>
                                            Telefone
                                            <input type="text" name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </label>
                                        <label>
                                            E-mail
                                            <input type="email" name="email" value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </label>
                                        <label>
                                            Endereco
                                            <input type="text" name="address" value="<?= htmlspecialchars($contact['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        </label>
                                        <label>
                                            Cidade
                                            <input type="text" name="city" value="<?= htmlspecialchars($contact['city'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        </label>
                                        <label>
                                            UF
                                            <select name="state" required>
                                                <?php foreach ($states as $state): ?>
                                                    <option value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>" <?= $contact['state'] === $state ? 'selected' : '' ?>><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <button type="submit"><span class="bi bi-check2-circle"></span>Salvar alteracoes</button>
                                    </form>
                                    <form method="post" action="/contacts/toggle">
                                        <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                                        <input type="hidden" name="active" value="<?= ((int) $contact['is_active']) === 1 ? '0' : '1' ?>">
                                        <button class="inline-button <?= ((int) $contact['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                                            <span class="bi <?= ((int) $contact['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                                            <?= ((int) $contact['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </details>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if ($contacts === []): ?>
                    <tr>
                        <td colspan="<?= $user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS) ? 6 : 5 ?>" class="muted">Nenhum contato cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
