<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

$error = null;
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit_enforce('login_ip', client_ip(), 20, 600); // 20 tentativas / 10 min por IP, qualquer e-mail
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Preencha e-mail e senha.';
    } elseif (!verify_captcha()) {
        $error = 'Não foi possível confirmar que você não é um robô. Tente novamente.';
    } elseif (($wait = login_lockout_seconds_remaining($email)) > 0) {
        $error = 'Muitas tentativas de login com este e-mail. Tente novamente em ' . ceil($wait / 60) . ' minuto(s).';
    } else {
        rate_limit_hit('login_ip', client_ip());
        try {
            $user = authenticate_user($email, $password);
            record_login_attempt($email, true);
            login_user((int) $user['id']);
            redirect($redirect && str_starts_with($redirect, '/') ? $redirect : base_url('minha-conta.php'));
        } catch (AuthServiceError $e) {
            record_login_attempt($email, false);
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-md rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Entrar</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Acesse sua conta para gerenciar anúncios e favoritos.</p>

    <?php if (!empty($_GET['redefinida'])): ?>
      <p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">Senha redefinida com sucesso. Faça login com sua nova senha.</p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">E-mail</label>
        <input type="email" name="email" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none">
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Senha</label>
        <input type="password" name="password" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm focus:border-brand-primary focus:outline-none">
      </div>
      <div class="mb-4 text-right">
        <a href="<?= base_url('esqueci-senha.php') ?>" class="text-xs font-medium text-brand-primary hover:underline">Esqueci minha senha</a>
      </div>
      <?= render_captcha_widget() ?>
      <button type="submit" class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Entrar</button>
      <p class="mt-4 text-center text-sm text-brand-text-secondary">Não tem uma conta? <a href="<?= base_url('cadastro.php') ?>" class="font-medium text-brand-primary hover:underline">Cadastre-se</a></p>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
