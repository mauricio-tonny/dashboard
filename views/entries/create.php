<?php
$title = 'Novo lançamento';
$categories = $categories ?? [];
$vendors = $vendors ?? [];
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-plus-circle"></span>
        <div>
            <span class="badge">Financeiro</span>
            <h1>Novo lançamento</h1>
            <p class="muted">Cadastre o lançamento e mantenha o A pagar sincronizado com a planilha.</p>
        </div>
    </div>
</section>

<section class="card section-card" style="max-width: 820px;">
    <h2 class="section-title"><span class="bi bi-pencil-square"></span>Dados do lançamento</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/entries" class="form-grid entry-form">
        <div class="entry-type-field">
            <span class="field-label">Tipo de lançamento</span>
            <div class="segmented-check-group entry-type-options">
                <label class="segmented-check-option entry-type-option is-expense">
                    <strong><input type="radio" name="type" value="expense" checked required> <span class="bi bi-arrow-down-circle"></span>Despesa</strong>
                    <small>Entra no A pagar e na aba mensal.</small>
                </label>
                <label class="segmented-check-option entry-type-option is-income">
                    <strong><input type="radio" name="type" value="income" required> <span class="bi bi-arrow-up-circle"></span>Receita</strong>
                    <small>Registra uma entrada financeira.</small>
                </label>
            </div>
        </div>

        <label>
            Data de vencimento
            <input type="date" name="date" required>
            <small class="muted">A data define o vencimento e a aba mensal. Ex.: novembro vai para a aba de novembro.</small>
        </label>

        <label>
            Valor
            <input type="text" name="amount" required placeholder="R$ 0,00" inputmode="decimal" data-money-input>
            <small class="muted">Digite o valor e a formatação será aplicada automaticamente.</small>
        </label>

        <label>
            Descrição
            <input type="text" name="description" required placeholder="Ex.: Aluguel, energia ou compra">
        </label>

        <label>
            Fornecedor
            <select name="vendor" data-entry-vendor required>
                <option value="">Selecione um fornecedor</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?= htmlspecialchars((string) $vendor['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $vendor['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <small class="muted">Selecione um fornecedor já cadastrado para manter os relatórios consistentes.</small>
        </label>

        <label>
            Categoria
            <select name="category" required>
                <option value="">Selecione uma categoria</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Modalidade
            <select name="modality" data-entry-modality>
                <option value="A VISTA">À vista</option>
                <option value="PARCELADO">Parcelado</option>
                <option value="ÚLTIMA PARCELA">Última parcela</option>
                <option value="RECORRENTE">Recorrente</option>
            </select>
        </label>

        <label data-entry-installments-wrapper hidden>
            Quantidade de parcelas
            <input type="number" name="installment_count" min="2" max="120" step="1" placeholder="Ex.: 3" inputmode="numeric" data-entry-installments>
            <small class="muted">Informe o valor de cada parcela. O lançamento será repetido nos meses seguintes.</small>
        </label>

        <label data-entry-recurrence-wrapper hidden>
            Recorrente até (opcional)
            <input type="date" name="recurrence_end_date" data-entry-recurrence-end>
            <small class="muted">Sem data final, será lançado em todos os meses disponíveis na planilha.</small>
        </label>

        <div class="actions">
            <button type="submit"><span class="bi bi-check2-circle"></span>Salvar</button>
            <a href="/finance/payable"><button class="inline-button" type="button">Cancelar</button></a>
        </div>
    </form>
</section>
<script>
    (() => {
        const form = document.querySelector('.entry-form');
        const vendor = form?.querySelector('[data-entry-vendor]');
        const types = form?.querySelectorAll('input[name="type"]');
        const modality = form?.querySelector('[data-entry-modality]');
        const installmentsWrapper = form?.querySelector('[data-entry-installments-wrapper]');
        const installments = form?.querySelector('[data-entry-installments]');
        const recurrenceWrapper = form?.querySelector('[data-entry-recurrence-wrapper]');
        const recurrenceEnd = form?.querySelector('[data-entry-recurrence-end]');

        const syncVendor = () => {
            const selected = form?.querySelector('input[name="type"]:checked')?.value;
            if (!vendor) {
                return;
            }

            vendor.required = selected === 'expense';
        };

        types?.forEach((type) => type.addEventListener('change', syncVendor));
        const syncInstallments = () => {
            const isInstallment = modality?.value === 'PARCELADO';

            if (!installmentsWrapper || !installments) {
                return;
            }

            installmentsWrapper.hidden = !isInstallment;
            installments.required = isInstallment;

            if (!isInstallment) {
                installments.value = '';
            }
        };

        modality?.addEventListener('change', syncInstallments);
        const syncRecurrence = () => {
            const isRecurring = modality?.value === 'RECORRENTE';

            if (!recurrenceWrapper || !recurrenceEnd) {
                return;
            }

            recurrenceWrapper.hidden = !isRecurring;

            if (!isRecurring) {
                recurrenceEnd.value = '';
            }
        };

        modality?.addEventListener('change', syncRecurrence);
        syncVendor();
        syncInstallments();
        syncRecurrence();
    })();
</script>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
