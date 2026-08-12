<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_service.php';

$error = null;
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['passwordConfirmation'] ?? '';

    if (mb_strlen($firstName) < 2) $fieldErrors['firstName'] = 'Informe seu nome.';
    if (mb_strlen($lastName) < 2) $fieldErrors['lastName'] = 'Informe seu sobrenome.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $fieldErrors['email'] = 'E-mail inválido.';
    if (mb_strlen($password) < 8) $fieldErrors['password'] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($password !== $passwordConfirmation) $fieldErrors['passwordConfirmation'] = 'As senhas não coincidem.';

    if (empty($fieldErrors)) {
        try {
            $userId = register_user($firstName, $lastName, $email, $phone, $password);
            login_user($userId);
            redirect(base_url('minha-conta.php'));
        } catch (AuthServiceError $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<div class="flex min-h-[60vh] items-center justify-center bg-brand-bg-subtle px-4 py-12">
  <div class="w-full max-w-md rounded-2xl border border-brand-border bg-white p-8 shadow-sm">
    <h1 class="mb-1 text-2xl font-bold">Criar conta</h1>
    <p class="mb-6 text-sm text-brand-text-secondary">Cadastre-se para favoritar imóveis e anunciar gratuitamente.</p>

    <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div class="grid grid-cols-2 gap-3">
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Nome</label>
          <input name="firstName" required value="<?= e($_POST['firstName'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php if (!empty($fieldErrors['firstName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['firstName']) ?></p><?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Sobrenome</label>
          <input name="lastName" required value="<?= e($_POST['lastName'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <?php if (!empty($fieldErrors['lastName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['lastName']) ?></p><?php endif; ?>
        </div>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">E-mail</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['email'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['email']) ?></p><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Telefone (WhatsApp)</label>
        <input type="tel" name="phone" placeholder="(47) 99999-9999" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Senha</label>
        <input type="password" name="password" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['password'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['password']) ?></p><?php endif; ?>
      </div>
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Confirmar senha</label>
        <input type="password" name="passwordConfirmation" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <?php if (!empty($fieldErrors['passwordConfirmation'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['passwordConfirmation']) ?></p><?php endif; ?>
      </div>
      <button type="submit" class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar conta</button>
      <p class="mt-4 text-center text-sm text-brand-text-secondary">Já tem uma conta? <a href="<?= base_url('login.php') ?>" class="font-medium text-brand-primary hover:underline">Entrar</a></p>
    </form>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
