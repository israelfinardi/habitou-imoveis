<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['passwordConfirmation'] ?? '';

    if (mb_strlen($password) < 8) {
        $error = 'A senha deve ter pelo menos 8 caracteres.';
    } elseif ($password !== $confirmation) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            reset_password($token, $password);
            redirect(base_url('login.php?redefinida=1'));
        } catch (AuthServiceError $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Redefinir senha';
require __DIR__ . '/includes/header.php';
?>
<div class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-md rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Redefinir senha</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Escolha uma nova senha para sua conta.</p>
    <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Nova senha</label>
        <input type="password" name="password" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Confirmar nova senha</label>
        <input type="password" name="passwordConfirmation" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
      <button type="submit" class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Redefinir senha</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
