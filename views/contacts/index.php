<?php
$title = 'Contatos';
$canManageContacts = $user->can(\App\Domain\Auth\Permission::MANAGE_CONTACTS);
$querySuffix = static fn (?string $type = null): string => http_build_query(array_filter([
    'type' => $type,
    'q' => $search ?? '',
], static fn ($value): bool => $value !== null && $value !== ''));
$typeBadges = static function (array $contact): string {
    $labels = [];

    if ((int) ($contact['is_vendor'] ?? 0) === 1) {
        $labels[] = 'Fornecedor';
    }

    if ((int) ($contact['is_client'] ?? 0) === 1) {
        $labels[] = 'Cliente';
    }

    return implode(' / ', $labels) ?: 'Não classificado';
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
            <span class="field-label">Classificação</span>
            <div class="segmented-check-group">
            <label class="segmented-check-option">
                <input type="checkbox" name="is_vendor" value="1" <?= ((int) ($contact['is_vendor'] ?? 0)) === 1 ? 'checked' : '' ?>>
                <span class="bi bi-shop"></span>
                Fornecedor
            </label>
            <label class="segmented-check-option">
                <input type="checkbox" name="is_client" value="1" <?= ((int) ($contact['is_client'] ?? 0)) === 1 ? 'checked' : '' ?>>
                <span class="bi bi-person-check"></span>
                Cliente
            </label>
            </div>
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
            <input type="text" name="document" value="<?= htmlspecialchars($contact['document'] ?? '', ENT_QUOTES, 'UTF-8') ?>" inputmode="numeric" data-mask="document">
        </label>
        <label>
            Telefone
            <input type="text" name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" inputmode="tel" data-mask="phone">
        </label>
        <label>
            E-mail
            <input type="email" name="email" value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <div class="form-section-title">Endereço</div>
        <label>
            CEP
            <input type="text" name="postal_code" value="<?= htmlspecialchars($contact['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" inputmode="numeric" data-mask="postal-code">
        </label>
        <label>
            Endereço
            <input type="text" name="address" value="<?= htmlspecialchars($contact['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-viacep-address>
        </label>
        <label>
            Número
            <input type="text" name="address_number" value="<?= htmlspecialchars($contact['address_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-viacep-number>
        </label>
        <label>
            Bairro
            <input type="text" name="neighborhood" value="<?= htmlspecialchars($contact['neighborhood'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-viacep-neighborhood>
        </label>
        <label>
            Complemento
            <input type="text" name="complement" value="<?= htmlspecialchars($contact['complement'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
            Cidade
            <input type="text" name="city" value="<?= htmlspecialchars($contact['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-viacep-city required>
        </label>
        <label>
            UF
            <select name="state" data-viacep-state required>
                <?php foreach ($states as $state): ?>
                    <option value="<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>" <?= ($contact['state'] ?? 'PR') === $state ? 'selected' : '' ?>><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="modal-footer-actions">
            <button type="button" class="inline-button button-light" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit"><span class="bi bi-check2-circle"></span><?= $isEdit ? 'Salvar alterações' : 'Salvar contato' ?></button>
        </div>
    </form>
<?php
};
$activeContacts = array_values(array_filter($contacts, static fn (array $contact): bool => ((int) $contact['is_active']) === 1));
$inactiveContacts = array_values(array_filter($contacts, static fn (array $contact): bool => ((int) $contact['is_active']) === 0));
$renderContactsTable = static function (array $rows, bool $canManageContacts) use ($typeBadges): void {
?>
    <div class="responsive-table">
        <table class="contacts-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Classificação</th>
                    <th>Contato</th>
                    <th>Cidade/UF</th>
                    <?php if ($canManageContacts): ?>
                        <th>Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $contact): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($contact['first_name'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($contact['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                            <span class="muted">
                                <?= htmlspecialchars($contact['document'] ?: 'Sem documento', ENT_QUOTES, 'UTF-8') ?>
                                <?= ($contact['vendor_id'] ?? null) !== null ? ' | Planilha' : '' ?>
                            </span>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($typeBadges($contact), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="contact-nowrap">
                            <?= htmlspecialchars($contact['phone'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="contact-nowrap"><?= htmlspecialchars(($contact['city'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($contact['state'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if ($canManageContacts): ?>
                            <td>
                                <div class="actions contact-actions">
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
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= $canManageContacts ? 5 : 4 ?>" class="muted">Nenhum contato encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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
            <p class="muted">Base inicial alimentada pelos fornecedores importados da planilha, pronta para enriquecer dados cadastrais aos poucos.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <?php if ($canManageContacts): ?>
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

<section class="report-summary-grid">
    <article class="card report-summary-card">
        <small>Total de contatos</small>
        <strong><?= (int) ($summary['total_contacts'] ?? 0) ?></strong>
    </article>
    <article class="card report-summary-card">
        <small>Fornecedores</small>
        <strong><?= (int) ($summary['total_vendors'] ?? 0) ?></strong>
    </article>
    <article class="card report-summary-card">
        <small>Clientes</small>
        <strong><?= (int) ($summary['total_clients'] ?? 0) ?></strong>
    </article>
    <article class="card report-summary-card">
        <small>Vindos da planilha</small>
        <strong><?= (int) ($summary['imported_vendors'] ?? 0) ?></strong>
    </article>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-funnel"></span>Filtros</h2>
    <form method="get" action="/contacts" class="form-grid align-end-form">
        <label>
            Buscar contato
            <input type="search" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, documento, telefone ou e-mail">
        </label>
        <label>
            Tipo
            <select name="type">
                <option value="" <?= $selectedType === null ? 'selected' : '' ?>>Todos</option>
                <option value="vendor" <?= $selectedType === 'vendor' ? 'selected' : '' ?>>Fornecedores</option>
                <option value="client" <?= $selectedType === 'client' ? 'selected' : '' ?>>Clientes</option>
            </select>
        </label>
        <button type="submit"><span class="bi bi-search"></span>Aplicar filtros</button>
    </form>
    <div class="actions">
        <a href="/contacts?<?= htmlspecialchars($querySuffix(null), ENT_QUOTES, 'UTF-8') ?>"><button class="inline-button <?= $selectedType === null ? '' : 'button-light' ?>" type="button">Todos</button></a>
        <a href="/contacts?<?= htmlspecialchars($querySuffix('vendor'), ENT_QUOTES, 'UTF-8') ?>"><button class="inline-button <?= $selectedType === 'vendor' ? '' : 'button-light' ?>" type="button">Fornecedores</button></a>
        <a href="/contacts?<?= htmlspecialchars($querySuffix('client'), ENT_QUOTES, 'UTF-8') ?>"><button class="inline-button <?= $selectedType === 'client' ? '' : 'button-light' ?>" type="button">Clientes</button></a>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-card-list"></span>Contatos cadastrados</h2>
    <?php $renderContactsTable($activeContacts, $canManageContacts); ?>

    <?php if ($inactiveContacts !== []): ?>
        <details class="inactive-contacts-panel">
            <summary>
                <span><span class="bi bi-archive"></span>Contatos inativos</span>
                <small><?= count($inactiveContacts) ?> contato(s)</small>
            </summary>
            <?php $renderContactsTable($inactiveContacts, $canManageContacts); ?>
        </details>
    <?php endif; ?>
</section>

<?php if ($canManageContacts): ?>
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
