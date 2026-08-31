<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
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
<!-- Se o JS carregar, assets/js/auth-modal.js esconde este bloco e abre o
     modal global automaticamente — pra quem chegou direto em /login.php
     ver a mesma experiência do resto do site (um único fluxo, sem página
     cheia duplicando o que o modal já mostra). Sem JS, isso aqui continua
     funcionando normalmente como fallback. -->
<div id="auth-fallback-page" class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-md rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Entrar</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Acesse sua conta para gerenciar anúncios e favoritos.</p>

    <?php if (!empty($_GET['redefinida'])): ?>
      <p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">Senha redefinida com sucesso. Faça login com sua nova senha.</p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if (oauth_configured('google') || oauth_configured('facebook')): ?>
      <div class="mb-5 flex flex-col gap-2.5">
        <?php if (oauth_configured('google')): ?>
          <a href="<?= e(base_url('actions/oauth_start.php?provider=google&redirect=' . urlencode($redirect))) ?>" class="flex items-center justify-center gap-2 rounded-full border border-brand-border py-2.5 text-sm font-semibold hover:bg-brand-bg-subtle">
            <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6 29.6 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.7-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.6 6 29.6 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.5 0 10.4-1.9 14.3-5.1l-6.6-5.6C29.6 34.9 26.9 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.6 39.6 16.3 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.6 5.6C39.9 37 44 31 44 24c0-1.3-.1-2.7-.4-3.5z"/></svg>
            Continuar com Google
          </a>
        <?php endif; ?>
        <?php if (oauth_configured('facebook')): ?>
          <a href="<?= e(base_url('actions/oauth_start.php?provider=facebook&redirect=' . urlencode($redirect))) ?>" class="flex items-center justify-center gap-2 rounded-full border border-brand-border py-2.5 text-sm font-semibold hover:bg-brand-bg-subtle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.7 4.53-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.89v2.26h3.32l-.53 3.49h-2.79V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
            Continuar com Facebook
          </a>
        <?php endif; ?>
      </div>
      <div class="mb-5 flex items-center gap-3 text-xs text-brand-text-secondary">
        <span class="h-px flex-1 bg-brand-border"></span>ou<span class="h-px flex-1 bg-brand-border"></span>
      </div>
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
