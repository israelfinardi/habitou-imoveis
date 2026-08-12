<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = require_login();
$success = null;
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (mb_strlen($firstName) < 2) $fieldErrors['firstName'] = 'Informe seu nome.';
    if (mb_strlen($lastName) < 2) $fieldErrors['lastName'] = 'Informe seu sobrenome.';

    if (empty($fieldErrors)) {
        db()->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?')
            ->execute([$firstName, $lastName, $phone ?: null, $user['id']]);
        $user['first_name'] = $firstName;
        $user['last_name'] = $lastName;
        $user['phone'] = $phone;
        $success = 'Dados atualizados com sucesso.';
    }
}

$pageTitle = 'Meus dados';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside><?php render_account_nav('dados'); ?></aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Meus dados</h1>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>
      <form method="post" class="max-w-md">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">E-mail</label>
          <input disabled value="<?= e($user['email']) ?>" class="w-full rounded-lg border border-brand-border bg-brand-bg-subtle px-3 py-2 text-sm text-brand-text-secondary">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">Nome</label>
            <input name="firstName" required value="<?= e($user['first_name']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">Sobrenome</label>
            <input name="lastName" required value="<?= e($user['last_name']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Telefone</label>
          <input name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar alterações</button>
      </form>
    </main>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
