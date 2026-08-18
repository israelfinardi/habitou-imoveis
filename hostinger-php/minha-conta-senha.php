<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = require_login();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['currentPassword'] ?? '';
    $new = $_POST['newPassword'] ?? '';
    $confirmation = $_POST['newPasswordConfirmation'] ?? '';

    if (mb_strlen($new) < 8) {
        $error = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($new !== $confirmation) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            change_password((int) $user['id'], $current, $new);
            $success = 'Senha alterada com sucesso.';
        } catch (AuthServiceError $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Alterar senha';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Alterar senha</h1>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
      <form method="post" class="max-w-md">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Senha atual</label>
          <input type="password" name="currentPassword" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Nova senha</label>
          <input type="password" name="newPassword" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Confirmar nova senha</label>
          <input type="password" name="newPasswordConfirmation" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Alterar senha</button>
      </form>
    </main>
    <aside><?php render_account_nav('senha'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
