<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit_enforce('password_reset_ip', client_ip(), 15, 3600); // 15 pedidos / hora por IP
    rate_limit_hit('password_reset_ip', client_ip());
    $email = trim(strtolower($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        request_password_reset($email);
    }
    $success = 'Se este e-mail estiver cadastrado, você receberá um link de recuperação em instantes.';
}

$pageTitle = 'Esqueci minha senha';
require __DIR__ . '/includes/header.php';
?>
<div class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-md rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Esqueci minha senha</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Informe o e-mail da sua conta. Enviaremos um link para redefinir sua senha.</p>

    <?php if ($success): ?>
      <p class="rounded-lg bg-brand-green/10 px-3 py-3 text-sm text-brand-green-hover"><?= e($success) ?></p>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">E-mail cadastrado</label>
          <input type="email" name="email" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <button type="submit" class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Enviar link de recuperação</button>
        <p class="mt-4 text-center text-sm"><a href="<?= base_url('login.php') ?>" class="font-medium text-brand-primary hover:underline">Voltar para o login</a></p>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
