<?php
$title = 'Relatorios';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-bar-chart-line"></span>
        <div>
            <span class="badge">Relatorios</span>
            <h1>Central de relatorios</h1>
            <p class="muted">Escolha o relatorio que deseja analisar. Cada area tem filtros proprios para manter a consulta mais leve.</p>
        </div>
    </div>
</section>

<section class="module-card-grid">
    <a class="module-card" href="/reports/categories">
        <span class="bi bi-tags"></span>
        <strong>Por categoria</strong>
        <p class="muted">Lista despesas por categoria e periodo, com fornecedor, parcela, status e valor.</p>
    </a>
    <a class="module-card" href="/reports/category-chart">
        <span class="bi bi-bar-chart"></span>
        <strong>Grafico por categoria</strong>
        <p class="muted">Compara categorias em barras verticais para leitura rapida dos maiores grupos.</p>
    </a>
    <a class="module-card" href="/reports/vendors">
        <span class="bi bi-person-lines-fill"></span>
        <strong>Por fornecedor</strong>
        <p class="muted">Agrupa despesas por fornecedor para identificar concentracao de gastos.</p>
    </a>
    <a class="module-card" href="/reports/paid-vs-received">
        <span class="bi bi-columns-gap"></span>
        <strong>Pago x recebido</strong>
        <p class="muted">Cruza entradas e saidas por periodo, com grafico de barras e tabela mensal.</p>
    </a>
    <a class="module-card" href="/reports/cashflow">
        <span class="bi bi-receipt-cutoff"></span>
        <strong>Fluxo de caixa</strong>
        <p class="muted">Modelo extrato com entradas, saidas e saldo acumulado do periodo.</p>
    </a>
    <a class="module-card" href="/reports/market">
        <span class="bi bi-basket2"></span>
        <strong>Mercado</strong>
        <p class="muted">Compare os totais de compras de mercado por periodo, com grafico e tabela mensal.</p>
    </a>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
