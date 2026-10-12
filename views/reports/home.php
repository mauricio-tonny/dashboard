<?php
$title = 'Relatórios';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bar-chart-line"></span>
        <div>
            <span class="badge">Relatórios</span>
            <h1>Central de relatórios</h1>
            <p class="muted">Escolha o relatório que deseja analisar. Cada área tem filtros próprios para manter a consulta mais leve.</p>
        </div>
    </div>
</section>

<section class="module-card-grid">
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CATEGORY_REPORT)): ?>
        <a class="module-card" href="/reports/categories">
            <span class="bi bi-tags"></span>
            <strong>Por categoria</strong>
            <p class="muted">Lista despesas por categoria e período, com fornecedor, parcela, status e valor.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CATEGORY_CHART_REPORT)): ?>
        <a class="module-card" href="/reports/category-chart">
            <span class="bi bi-bar-chart"></span>
            <strong>Gráfico por categoria</strong>
            <p class="muted">Compara categorias em barras verticais para leitura rápida dos maiores grupos.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_VENDOR_REPORT)): ?>
        <a class="module-card" href="/reports/vendors">
            <span class="bi bi-person-lines-fill"></span>
            <strong>Por fornecedor</strong>
            <p class="muted">Agrupa despesas por fornecedor para identificar concentração de gastos.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_PAID_VS_RECEIVED_REPORT)): ?>
        <a class="module-card" href="/reports/paid-vs-received">
            <span class="bi bi-columns-gap"></span>
            <strong>Pago x recebido</strong>
            <p class="muted">Cruza entradas e saídas por período, com gráfico de barras e tabela mensal.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CASHFLOW_REPORT)): ?>
        <a class="module-card" href="/reports/cashflow">
            <span class="bi bi-receipt-cutoff"></span>
            <strong>Fluxo de caixa</strong>
            <p class="muted">Modelo extrato com entradas, saídas e saldo acumulado do período.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_MODALITY_REPORT)): ?>
        <a class="module-card" href="/reports/modality">
            <span class="bi bi-credit-card-2-front"></span>
            <strong>Por modalidade</strong>
            <p class="muted">Compara despesas à vista, parceladas, últimas parcelas e recorrentes por período.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_KARINA_STATEMENT_REPORT)): ?>
        <a class="module-card" href="/reports/karina-statement">
            <span class="bi bi-arrow-left-right"></span>
            <strong>Extrato Karina</strong>
            <p class="muted">Controle de crédito x débito, com saldo do empréstimo e histórico recente.</p>
        </a>
    <?php endif; ?>
    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_MARKET_REPORT)): ?>
        <a class="module-card" href="/reports/market">
            <span class="bi bi-basket2"></span>
            <strong>Mercado</strong>
            <p class="muted">Compare os totais de compras de mercado por período, com gráfico e tabela mensal.</p>
        </a>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
