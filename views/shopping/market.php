<?php
$title = 'Mercado';
$formatMoney = static fn ($value): string => $value === null ? '-' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$formatDecimal = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, ',', ''), '0'), ',');
$monthLabel = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '-';
    }

    return (new DateTimeImmutable($date))->format('m/Y');
};
$initial = static fn (string $name): string => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
$selectedListId = $selectedMarketList === null ? 0 : (int) $selectedMarketList['id'];
$formatAccessKey = static function (?string $key): string {
    $digits = preg_replace('/\D+/', '', (string) $key) ?? '';

    return trim((string) preg_replace('/(\d{4})(?=\d)/', '$1 ', $digits));
};
$formatDocument = static function (?string $document): string {
    $digits = preg_replace('/\D+/', '', (string) $document) ?? '';

    if (strlen($digits) !== 14) {
        return $digits ?: '-';
    }

    return substr($digits, 0, 2) . '.' . substr($digits, 2, 3) . '.' . substr($digits, 5, 3) . '/' . substr($digits, 8, 4) . '-' . substr($digits, 12, 2);
};
$itemsSubtotal = array_reduce(
    $marketItems,
    static fn (float $carry, array $item): float => $carry + (float) ($item['subtotal_amount'] ?? 0),
    0.0
);
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-basket2"></span>
        <div>
            <span class="badge">Compras</span>
            <h1>Mercado</h1>
            <p class="muted">Crie a lista mensal, marque os itens no mercado e anexe NFC-e/NF-e para facilitar relatórios futuros.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card shopping-hero">
    <div>
        <h2 class="section-title"><span class="bi bi-calendar-plus"></span>Mercado mensal</h2>
        <p class="muted">A sugestao padrao e sempre o proximo mes.</p>
    </div>
    <form method="post" action="/shopping/market/lists" class="inline-form align-end-form">
        <label>
            Mes da lista
            <input type="month" name="reference_month" value="<?= htmlspecialchars(substr($nextMonth, 0, 7), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <button class="inline-button" type="submit"><span class="bi bi-calendar-plus"></span>Criar/selecionar lista</button>
    </form>
</section>

<section class="grid">
    <article class="card section-card">
        <h2 class="section-title"><span class="bi bi-calendar3"></span>Listas de mercado</h2>
        <?php foreach ($marketLists as $list): ?>
            <a class="month-pill <?= (int) $list['id'] === $selectedListId ? 'is-active' : '' ?>" href="/shopping/market?market_list_id=<?= (int) $list['id'] ?>">
                <?= htmlspecialchars($monthLabel($list['reference_month']), ENT_QUOTES, 'UTF-8') ?>
                <small><?= (int) $list['checked_count'] ?>/<?= (int) $list['item_count'] ?> itens</small>
            </a>
        <?php endforeach; ?>
        <?php if ($marketLists === []): ?>
            <p class="muted">Crie a primeira lista para comecar.</p>
        <?php endif; ?>
    </article>

    <article class="card section-card">
        <h2 class="section-title"><span class="bi bi-cart-check"></span>Itens do mercado</h2>
        <?php if ($selectedMarketList): ?>
            <p class="muted">
                Lista de <?= htmlspecialchars($monthLabel($selectedMarketList['reference_month']), ENT_QUOTES, 'UTF-8') ?>.
                Total final: <?= htmlspecialchars($formatMoney($selectedMarketList['total_amount']), ENT_QUOTES, 'UTF-8') ?>.
                Subtotal dos itens: <?= htmlspecialchars($formatMoney($itemsSubtotal), ENT_QUOTES, 'UTF-8') ?>
            </p>
            <form method="post" action="/shopping/market/items" class="form-grid market-price-form" data-market-item-form>
                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                <label>
                    Nome do item
                    <input type="text" name="name" placeholder="Ex.: Arroz, Sabao em po, Leite" required>
                </label>
                <label>
                    Sessao
                    <select name="section_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($marketSections as $section): ?>
                            <option value="<?= (int) $section['id'] ?>"><?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Quantidade
                    <input type="number" name="quantity" min="0.001" step="0.001" value="1" required>
                </label>
                <label>
                    Valor unitario
                    <input type="text" name="unit_amount" inputmode="decimal" placeholder="0,00" data-unit-amount>
                </label>
                <label>
                    Valor
                    <input type="text" name="amount" inputmode="decimal" placeholder="0,00">
                </label>
                <label>
                    Sub total
                    <input type="text" name="subtotal_preview" placeholder="0,00" data-subtotal-preview readonly>
                </label>
                <div class="form-actions">
                    <button type="submit"><span class="bi bi-plus-circle"></span>Adicionar item</button>
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
                            <button class="check-button" type="submit">
                                <span class="bi <?= ((int) $item['is_checked']) === 1 ? 'bi-check2' : 'bi-cart-plus' ?>"></span>
                                <?= ((int) $item['is_checked']) === 1 ? 'OK' : 'Pegar' ?>
                            </button>
                        </form>
                        <div class="market-item-copy">
                            <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>
                                <?= htmlspecialchars($item['section_name'] ?? $item['section'], ENT_QUOTES, 'UTF-8') ?>
                                | Qtd: <?= htmlspecialchars($formatDecimal($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8') ?>
                                | Unit.: <?= htmlspecialchars($formatMoney($item['unit_amount']), ENT_QUOTES, 'UTF-8') ?>
                                | Subtotal: <?= htmlspecialchars($formatMoney($item['subtotal_amount']), ENT_QUOTES, 'UTF-8') ?>
                            </small>
                        </div>
                        <details>
                            <summary><span class="bi bi-pencil-square"></span>Editar</summary>
                            <form method="post" action="/shopping/market/items/update" class="compact-form market-price-form" data-market-item-form>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <select name="section_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($marketSections as $section): ?>
                                        <option value="<?= (int) $section['id'] ?>" <?= (int) ($item['section_id'] ?? 0) === (int) $section['id'] || (($item['section_id'] ?? null) === null && ($item['section'] ?? '') === $section['name']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($section['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity" min="0.001" step="0.001" value="<?= htmlspecialchars((string) ($item['quantity'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>" required>
                                <input type="text" name="unit_amount" inputmode="decimal" value="<?= htmlspecialchars($item['unit_amount'] === null ? '' : number_format((float) $item['unit_amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Valor unitario" data-unit-amount>
                                <input type="text" name="amount" inputmode="decimal" value="<?= htmlspecialchars($item['amount'] === null ? '' : number_format((float) $item['amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Valor">
                                <input type="text" name="subtotal_preview" value="<?= htmlspecialchars($item['subtotal_amount'] === null ? '' : number_format((float) $item['subtotal_amount'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Sub total" data-subtotal-preview readonly>
                                <button type="submit"><span class="bi bi-check2-circle"></span>Salvar</button>
                            </form>
                            <form method="post" action="/shopping/market/items/delete">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                                <button class="button-danger" type="submit"><span class="bi bi-trash3"></span>Remover</button>
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
                <button class="inline-button" type="submit"><span class="bi bi-flag"></span>Finalizar lista</button>
            </form>
        <?php else: ?>
            <p class="muted">Selecione ou crie uma lista mensal.</p>
        <?php endif; ?>
    </article>
</section>

<?php if ($selectedMarketList): ?>
    <section class="card section-card">
        <h2 class="section-title"><span class="bi bi-receipt"></span>NFC-e / NF-e anexadas</h2>
        <p class="muted">
            Escolha uma das formas abaixo para vincular notas a lista de <?= htmlspecialchars($monthLabel($selectedMarketList['reference_month']), ENT_QUOTES, 'UTF-8') ?>.
            XML importa itens automaticamente; PDF/imagem ficam como anexo; chave de acesso salva os metadados para consulta publica.
        </p>
        <div class="grid">
            <form method="post" action="/shopping/market/invoices" enctype="multipart/form-data" class="soft-panel compact-form">
                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                <h3><span class="bi bi-file-earmark-arrow-up"></span> Upload de arquivo</h3>
                <p class="muted">Use XML para importar itens. PDF, JPG e PNG ficam anexados para conferencia.</p>
                <label>
                    Mes da lista
                    <input type="text" value="<?= htmlspecialchars($monthLabel($selectedMarketList['reference_month']), ENT_QUOTES, 'UTF-8') ?>" readonly>
                </label>
                <label>
                    Arquivo da nota
                    <input type="file" name="invoice" accept=".pdf,.xml,.jpg,.jpeg,.png" required>
                </label>
                <button type="submit"><span class="bi bi-upload"></span>Anexar nota</button>
            </form>

            <form method="post" action="/shopping/market/access-key" class="soft-panel compact-form">
                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                <h3><span class="bi bi-key"></span> Chave de acesso</h3>
                <p class="muted">Quando nao houver XML, salve a chave para consulta publica e controle da nota.</p>
                <label>
                    Chave de acesso
                    <input type="text" name="access_key" inputmode="numeric" placeholder="0000 0000 0000 0000..." maxlength="60" required>
                </label>
                <button type="submit"><span class="bi bi-link-45deg"></span>Salvar chave</button>
            </form>
        </div>
        <div class="settings-list">
            <?php foreach ($marketInvoices as $invoice): ?>
                <div class="settings-item">
                    <?php if (($invoice['source_type'] ?? 'file') === 'access_key'): ?>
                        <strong>Chave <?= htmlspecialchars($formatAccessKey($invoice['access_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <small>
                            NFC-e <?= htmlspecialchars((string) ($invoice['document_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            | Serie <?= htmlspecialchars((string) ($invoice['document_series'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            | CNPJ <?= htmlspecialchars($formatDocument($invoice['issuer_document'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                            | <?= htmlspecialchars((string) $invoice['created_at'], ENT_QUOTES, 'UTF-8') ?>
                        </small>
                        <?php if (!empty($invoice['public_url'])): ?>
                            <a href="<?= htmlspecialchars((string) $invoice['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <button class="inline-button" type="button"><span class="bi bi-box-arrow-up-right"></span>Abrir consulta publica</button>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <strong><?= htmlspecialchars($invoice['original_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= number_format(((int) $invoice['file_size']) / 1024, 1, ',', '.') ?> KB | <?= htmlspecialchars((string) $invoice['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if ($marketInvoices === []): ?>
                <p class="muted">Nenhuma nota anexada para esta lista.</p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<script>
    document.querySelectorAll('[data-market-item-form]').forEach((form) => {
        const quantity = form.querySelector('[name="quantity"]');
        const unitAmount = form.querySelector('[name="unit_amount"]');
        const preview = form.querySelector('[data-subtotal-preview]');
        const parseMoney = (value) => {
            const normalized = value.replace(/\./g, '').replace(',', '.').trim();
            return normalized === '' ? null : Number(normalized);
        };
        const formatMoney = (value) => value.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const updateSubtotal = () => {
            const qty = Number(String(quantity?.value ?? '').replace(',', '.'));
            const unit = parseMoney(String(unitAmount?.value ?? ''));

            if (!preview || Number.isNaN(qty) || qty <= 0 || unit === null || Number.isNaN(unit)) {
                if (preview) {
                    preview.value = '';
                }
                return;
            }

            preview.value = formatMoney(qty * unit);
        };

        quantity?.addEventListener('input', updateSubtotal);
        unitAmount?.addEventListener('input', updateSubtotal);
        updateSubtotal();
    });
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
