<?php
$title = 'Compras';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-basket2"></span>
        <div>
            <span class="badge">Compras</span>
            <h1>Central de compras</h1>
            <p class="muted">Escolha qual lista deseja consultar ou atualizar. As listas agora ficam separadas para manter a rotina mais organizada.</p>
        </div>
    </div>

    <div class="page-hero-actions">
        <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SHOPPING_SETTINGS)): ?>
            <a href="/admin/shopping-settings"><button class="inline-button" type="button"><span class="bi bi-sliders"></span>Configuracao compras</button></a>
        <?php endif; ?>
    </div>
</section>

<section class="module-card-grid">
    <a class="module-card" href="/shopping/market">
        <span class="bi bi-basket2"></span>
        <h2>Mercado</h2>
        <p class="muted">Lista mensal com checklist, valor total e anexos de NFC-e/NF-e.</p>
    </a>
    <a class="module-card" href="/shopping/home">
        <span class="bi bi-house-heart"></span>
        <h2>Para casa</h2>
        <p class="muted">Itens por comodo, valor previsto e prioridade.</p>
    </a>
    <a class="module-card" href="/shopping/family">
        <span class="bi bi-people"></span>
        <h2>Para a familia</h2>
        <p class="muted">Itens vinculados a quem precisa comprar ou receber.</p>
    </a>
    <a class="module-card" href="/shopping/vehicle">
        <span class="bi bi-car-front"></span>
        <h2>Para o veiculo</h2>
        <p class="muted">Itens por veiculo e area de manutencao.</p>
    </a>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
