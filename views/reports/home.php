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
            <p class="muted">Escolha o indicador que deseja analisar. Esta área vai crescer conforme novos relatórios forem implementados.</p>
        </div>
    </div>
</section>

<section class="module-card-grid">
    <a class="module-card" href="/reports/market">
        <span class="bi bi-basket2"></span>
        <strong>Mercado</strong>
        <p class="muted">Compare os totais de compras de mercado por período, com grafico de barras e tabela mensal.</p>
    </a>
    <div class="module-card">
        <span class="bi bi-pie-chart"></span>
        <strong>Despesas por DR</strong>
        <p class="muted">Reservado para o grafico pizza de despesas por categoria/DR.</p>
    </div>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
