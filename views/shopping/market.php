<?php
$title = 'Mercado';
$formatMoney = static fn ($value): string => $value === null ? '-' : 'R$ ' . number_format((float) $value, 2, ',', '.');
$monthLabel = static function (?string $date): string {
    if ($date === null || $date === '') {
        return '-';
    }

    return (new DateTimeImmutable($date))->format('m/Y');
};
$initial = static fn (string $name): string => mb_strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
$selectedListId = $selectedMarketList === null ? 0 : (int) $selectedMarketList['id'];
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
                            <small><?= htmlspecialchars($item['section'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <details>
                            <summary><span class="bi bi-pencil-square"></span>Editar</summary>
                            <form method="post" action="/shopping/market/items/update" class="compact-form">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
                                <input type="text" name="name" value="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                <input type="text" name="section" value="<?= htmlspecialchars($item['section'], ENT_QUOTES, 'UTF-8') ?>" required>
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
        <form method="post" action="/shopping/market/invoices" enctype="multipart/form-data" class="inline-form align-end-form">
            <input type="hidden" name="list_id" value="<?= $selectedListId ?>">
            <label>
                Arquivo da nota
                <input type="file" name="invoice" accept=".pdf,.xml,.jpg,.jpeg,.png" required>
            </label>
            <button class="inline-button" type="submit"><span class="bi bi-upload"></span>Anexar nota</button>
        </form>
        <div class="settings-list">
            <?php foreach ($marketInvoices as $invoice): ?>
                <div class="settings-item">
                    <strong><?= htmlspecialchars($invoice['original_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= number_format(((int) $invoice['file_size']) / 1024, 1, ',', '.') ?> KB | <?= htmlspecialchars((string) $invoice['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            <?php endforeach; ?>
            <?php if ($marketInvoices === []): ?>
                <p class="muted">Nenhuma nota anexada para esta lista.</p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
