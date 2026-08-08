<?php
$title = 'Contatos';
$typeBadges = static function (array $contact): string {
    $labels = [];

    if ((int) ($contact['is_vendor'] ?? 0) === 1) {
        $labels[] = 'Fornecedor';
    }

    if ((int) ($contact['is_client'] ?? 0) === 1) {
        $labels[] = 'Cliente';
    }

    return implode(' / ', $labels) ?: 'Nao classificado';
};
$renderContactForm = static function (array $states, ?array $contact = null): void {
    $isEdit = $contact !== null;
    $action = $isEdit ? '/contacts/update' : '/contacts';
?>
    <form method="post" action="<?= $action ?>" class="form-grid">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
        <?php endif; ?>
        <div class="form-check-group">
            <span class="field-label">Classificacao</span>
            <label class="check-option">
                <input type="checkbox" name="is_vendor" value="1" <?= ((int) ($contact['is_vendor'] ?? 0)) === 1 ? 'checked' : '' ?>>
                Fornecedor
            </label>
            <label class="check-option">
                <input type="checkbox" name="is_client" value="1" <?= ((int) ($contact['is_client'] ?? 0)) === 1 ? 'checked' : '' ?>>
                Cliente
            </label>
        </div>
        <label>
            Nome
            <input type="text" name="first_name" value="<?= htmlspecialchars($contact['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
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
            <input type="text" name="city" value="<?= htmlspecialchars($contact['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>
            UF
            <select name="state" required>
                <?php foreach ($states as $state): ?>
                    <option value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>" <?= ($contact['state'] ?? 'PR') === $state ? 'selected' : '' ?>><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="modal-footer-actions">
            <button type="button" class="inline-button button-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit"><span class="bi bi-check2-circle"></span><?= $isEdit ? 'Salvar alteracoes' : 'Salvar contato' ?></button>
        </div>
    </form>
<?php
};
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-person-lines-fill"></span>
        <div>
            <span class="badge">Contatos</span>
            <h1>Fornecedores e clientes</h1>
            <p class="muted">Consulte contatos e abra o cadastro somente quando precisar criar ou alterar alguem.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <a href="/contacts"><button class="inline-button" type="button">Todos</button></a>
        <a href="/contacts?type=vendor"><button class="inline-button" type="button">Fornecedores</button></a>
        <a href="/contacts?type=client"><button class="inline-button" type="button">Clientes</button></a>
        <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
            <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#createContactModal">
                <span class="bi bi-person-plus"></span>Criar contato
            </button>
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
    <h2 class="section-title"><span class="bi bi-card-list"></span>Contatos cadastrados</h2>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Classificacao</th>
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
                        <td><span class="badge"><?= htmlspecialchars($typeBadges($contact), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?= htmlspecialchars($contact['phone'] ?? '-', ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="muted"><?= htmlspecialchars($contact['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td><?= htmlspecialchars($contact['city'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($contact['state'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= ((int) $contact['is_active']) === 1 ? 'Ativo' : 'Inativo' ?></td>
                        <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
                            <td>
                                <div class="actions">
                                    <button class="inline-button" type="button" data-bs-toggle="modal" data-bs-target="#editContactModal<?= (int) $contact['id'] ?>">
                                        <span class="bi bi-pencil-square"></span>Editar
                                    </button>
                                    <form method="post" action="/contacts/toggle">
                                        <input type="hidden" name="id" value="<?= (int) $contact['id'] ?>">
                                        <input type="hidden" name="active" value="<?= ((int) $contact['is_active']) === 1 ? '0' : '1' ?>">
                                        <button class="inline-button <?= ((int) $contact['is_active']) === 1 ? 'button-danger' : '' ?>" type="submit">
                                            <span class="bi <?= ((int) $contact['is_active']) === 1 ? 'bi-slash-circle' : 'bi-arrow-counterclockwise' ?>"></span>
                                            <?= ((int) $contact['is_active']) === 1 ? 'Desativar' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </div>
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

<?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS)): ?>
    <div class="modal fade" id="createContactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5"><span class="bi bi-person-plus"></span>Criar contato</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php $renderContactForm($states); ?>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($contacts as $contact): ?>
        <div class="modal fade" id="editContactModal<?= (int) $contact['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5"><span class="bi bi-pencil-square"></span>Editar <?= htmlspecialchars($contact['first_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <?php $renderContactForm($states, $contact); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
