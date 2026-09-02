<?php
$title = 'Alterar senha';
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-key"></span>
        <div>
            <span class="badge">Minha conta</span>
            <h1>Alterar senha</h1>
            <p class="muted">Atualize sua senha de acesso mantendo a conta protegida.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="card section-card account-password-card">
    <h2 class="section-title"><span class="bi bi-shield-lock"></span>Nova senha</h2>
    <form method="post" action="/account/password" class="account-password-form">
        <div class="account-password-fields">
            <label>
                Senha atual
                <input type="password" name="current_password" autocomplete="current-password" required>
            </label>
            <label>
                Nova senha
                <input type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                <small class="muted">Use pelo menos 8 caracteres.</small>
            </label>
            <label>
                Confirmar nova senha
                <input type="password" name="new_password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>
        </div>
        <div class="account-password-actions">
            <p class="muted">Por segurança, informe sua senha atual antes de definir uma nova.</p>
            <button type="submit"><span class="bi bi-check2-circle"></span>Salvar nova senha</button>
        </div>
    </form>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
