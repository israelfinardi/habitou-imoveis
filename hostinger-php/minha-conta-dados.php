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
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $serviceArea = trim($_POST['serviceArea'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $notifyEmail = trim($_POST['notifyEmail'] ?? '');

    if (mb_strlen($firstName) < 2) $fieldErrors['firstName'] = 'Informe seu nome.';
    if (mb_strlen($lastName) < 2) $fieldErrors['lastName'] = 'Informe seu sobrenome.';
    if ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) $fieldErrors['notifyEmail'] = 'E-mail de notificação inválido.';

    if (empty($fieldErrors)) {
        db()->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, whatsapp = ?, website = ?, service_area = ?, bio = ?, notify_email = ? WHERE id = ?')
            ->execute([$firstName, $lastName, $phone ?: null, $whatsapp ?: null, $website ?: null, $serviceArea ?: null, $bio ?: null, $notifyEmail ?: null, $user['id']]);
        $user = array_merge($user, [
            'first_name' => $firstName, 'last_name' => $lastName, 'phone' => $phone,
            'whatsapp' => $whatsapp, 'website' => $website, 'service_area' => $serviceArea, 'bio' => $bio,
            'notify_email' => $notifyEmail,
        ]);
        $success = 'Dados atualizados com sucesso.';
    }
}

$pageTitle = 'Meus dados';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Meus dados</h1>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>
      <?php if ($fieldErrors): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e(implode(' ', $fieldErrors)) ?></p><?php endif; ?>

      <div class="mb-6 flex items-center gap-4">
        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
          <?php if (!empty($user['avatar_url'])): ?>
            <img id="avatar-preview" src="<?= e($user['avatar_url']) ?>" class="h-full w-full object-cover" alt="">
          <?php else: ?>
            <span id="avatar-preview-fallback" class="flex h-full w-full items-center justify-center text-2xl font-semibold text-brand-primary"><?= e(mb_strtoupper(mb_substr($user['first_name'], 0, 1))) ?></span>
            <img id="avatar-preview" src="" class="hidden h-full w-full object-cover" alt="">
          <?php endif; ?>
        </div>
        <div>
          <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">
            <span id="avatar-upload-label">Alterar foto de perfil</span>
            <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/webp" class="hidden">
          </label>
          <p class="mt-1 text-xs text-brand-text-secondary">JPG, PNG ou WEBP, até 8MB.</p>
        </div>
      </div>

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
        <div class="grid grid-cols-2 gap-3">
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">Telefone</label>
            <input name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
          <div class="mb-4">
            <label class="mb-1 block text-sm font-medium">WhatsApp</label>
            <input name="whatsapp" value="<?= e($user['whatsapp'] ?? '') ?>" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-brand-text-secondary">Se vazio, usa o telefone acima.</p>
          </div>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Site</label>
          <input name="website" value="<?= e($user['website'] ?? '') ?>" placeholder="https://..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Cidade(s) de atuação</label>
          <input name="serviceArea" value="<?= e($user['service_area'] ?? '') ?>" placeholder="Ex.: São Paulo, Guarulhos e Osasco" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Sobre você</label>
          <textarea name="bio" rows="4" placeholder="Uma breve apresentação exibida no seu perfil público." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"><?= e($user['bio'] ?? '') ?></textarea>
        </div>
        <div class="mb-4 border-t border-brand-border pt-4">
          <label class="mb-1 block text-sm font-medium">E-mail de notificação</label>
          <input type="email" name="notifyEmail" value="<?= e($user['notify_email'] ?? '') ?>" placeholder="<?= e($user['email']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
          <p class="mt-1 text-xs text-brand-text-secondary">Para onde vão os avisos de novo contato. Deixe em branco para usar o e-mail da conta (<?= e($user['email']) ?>).</p>
        </div>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar alterações</button>
      </form>
    </main>
    <aside><?php render_account_nav('dados', in_array($user['role'], ['AGENCY_ADMIN'], true)); ?></aside>
  </div>
</div>
<script>
document.getElementById('avatar-input').addEventListener('change', function (e) {
  const file = e.target.files[0];
  if (!file) return;
  const label = document.getElementById('avatar-upload-label');
  label.textContent = 'Enviando...';
  const formData = new FormData();
  formData.append('avatar', file);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch(APP_BASE + 'actions/upload_avatar.php', { method: 'POST', body: formData })
    .then((r) => r.json())
    .then((data) => {
      label.textContent = 'Alterar foto de perfil';
      if (data.url) {
        const img = document.getElementById('avatar-preview');
        img.src = data.url;
        img.classList.remove('hidden');
        const fallback = document.getElementById('avatar-preview-fallback');
        if (fallback) fallback.classList.add('hidden');
      }
      if (data.error) alert(data.error);
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
