<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/avatar.php';

$user = require_login();
$success = null;
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $section = $_POST['section'] ?? '';

    if ($section === 'personal') {
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        if (mb_strlen($firstName) < 2) $fieldErrors['firstName'] = 'Informe seu nome.';
        if (mb_strlen($lastName) < 2) $fieldErrors['lastName'] = 'Informe seu sobrenome.';
        if (empty($fieldErrors)) {
            db()->prepare('UPDATE users SET first_name = ?, last_name = ? WHERE id = ?')->execute([$firstName, $lastName, $user['id']]);
            $user = array_merge($user, ['first_name' => $firstName, 'last_name' => $lastName]);
            $success = $section;
        }
    } elseif ($section === 'contact') {
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $notifyEmail = trim($_POST['notifyEmail'] ?? '');
        if ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) $fieldErrors['notifyEmail'] = 'E-mail de notificação inválido.';
        if (empty($fieldErrors)) {
            db()->prepare('UPDATE users SET phone = ?, whatsapp = ?, notify_email = ? WHERE id = ?')
                ->execute([$phone ?: null, $whatsapp ?: null, $notifyEmail ?: null, $user['id']]);
            $user = array_merge($user, ['phone' => $phone, 'whatsapp' => $whatsapp, 'notify_email' => $notifyEmail]);
            $success = $section;
        }
    } elseif ($section === 'address') {
        $zipCode = trim($_POST['zipCode'] ?? '');
        $cityLabel = trim($_POST['cityLabel'] ?? '');
        $address = trim($_POST['address'] ?? '');
        ['city' => $city, 'state' => $state] = parse_city_state_label($cityLabel);
        db()->prepare('UPDATE users SET zip_code = ?, city = ?, state = ?, address = ? WHERE id = ?')
            ->execute([$zipCode ?: null, $city, $state, $address ?: null, $user['id']]);
        $user = array_merge($user, ['zip_code' => $zipCode, 'city' => $city, 'state' => $state, 'address' => $address]);
        $success = $section;
    } elseif ($section === 'social') {
        $website = trim($_POST['website'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');
        $facebook = trim($_POST['facebook'] ?? '');
        db()->prepare('UPDATE users SET website = ?, instagram = ?, facebook = ? WHERE id = ?')
            ->execute([$website ?: null, $instagram ?: null, $facebook ?: null, $user['id']]);
        $user = array_merge($user, ['website' => $website, 'instagram' => $instagram, 'facebook' => $facebook]);
        $success = $section;
    } elseif ($section === 'professional') {
        $creci = trim($_POST['creci'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $serviceArea = trim($_POST['serviceArea'] ?? '');
        db()->prepare('UPDATE users SET creci = ?, cnpj = ?, service_area = ? WHERE id = ?')
            ->execute([$creci ?: null, $cnpj ?: null, $serviceArea ?: null, $user['id']]);
        $user = array_merge($user, ['creci' => $creci, 'cnpj' => $cnpj, 'service_area' => $serviceArea]);
        $success = $section;
    } elseif ($section === 'about') {
        $bio = trim($_POST['bio'] ?? '');
        db()->prepare('UPDATE users SET bio = ? WHERE id = ?')->execute([$bio ?: null, $user['id']]);
        $user = array_merge($user, ['bio' => $bio]);
        $success = $section;
    }
}

$pageTitle = 'Perfil';
require __DIR__ . '/includes/header.php';

$cards = [
    'personal' => 'Dados pessoais atualizados.',
    'contact' => 'Contato atualizado.',
    'address' => 'Endereço atualizado.',
    'social' => 'Redes sociais atualizadas.',
    'professional' => 'Dados profissionais atualizados.',
    'about' => 'Apresentação atualizada.',
];
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Perfil</h1>
      <p class="mb-6 text-sm text-brand-text-secondary">Cada card abaixo salva de forma independente — edite só o que precisar.</p>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

        <!-- Foto de perfil -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm sm:col-span-2">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Foto de perfil</h2>
          <div class="flex items-center gap-4">
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
        </div>

        <!-- Dados pessoais -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Dados pessoais</h2>
          <?php if ($success === 'personal'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['personal']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="personal">
            <div class="mb-3">
              <label class="mb-1 block text-sm font-medium">E-mail</label>
              <input disabled value="<?= e($user['email']) ?>" class="w-full rounded-lg border border-brand-border bg-brand-bg-subtle px-3 py-2 text-sm text-brand-text-secondary">
            </div>
            <div class="mb-3 grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium">Nome</label>
                <input name="firstName" required value="<?= e($user['first_name']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                <?php if (!empty($fieldErrors['firstName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['firstName']) ?></p><?php endif; ?>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Sobrenome</label>
                <input name="lastName" required value="<?= e($user['last_name']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                <?php if (!empty($fieldErrors['lastName'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['lastName']) ?></p><?php endif; ?>
              </div>
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

        <!-- Contato -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Contato</h2>
          <?php if ($success === 'contact'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['contact']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="contact">
            <div class="mb-3 grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium">Telefone</label>
                <input name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">WhatsApp</label>
                <input name="whatsapp" value="<?= e($user['whatsapp'] ?? '') ?>" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
            </div>
            <div class="mb-3">
              <label class="mb-1 block text-sm font-medium">E-mail de notificação</label>
              <input type="email" name="notifyEmail" value="<?= e($user['notify_email'] ?? '') ?>" placeholder="<?= e($user['email']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              <?php if (!empty($fieldErrors['notifyEmail'])): ?><p class="mt-1 text-xs text-red-600"><?= e($fieldErrors['notifyEmail']) ?></p><?php endif; ?>
              <p class="mt-1 text-xs text-brand-text-secondary">Para onde vão os avisos de novo contato. Vazio usa o e-mail da conta.</p>
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

        <!-- Endereço -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Endereço</h2>
          <?php if ($success === 'address'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['address']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="address">
            <div class="mb-3 grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium">CEP</label>
                <input name="zipCode" value="<?= e($user['zip_code'] ?? '') ?>" placeholder="00000-000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Cidade</label>
                <input type="text" name="cityLabel" class="js-city-picker w-full rounded-lg border border-brand-border px-3 py-2 text-sm"
                       list="cidades-datalist-perfil" autocomplete="off" placeholder="Digite o nome da cidade..."
                       value="<?= e(($user['city'] ?? '') !== '' && ($user['state'] ?? '') !== '' ? $user['city'] . ' (' . array_search($user['state'], BRAZIL_STATES) . ')' : '') ?>">
                <datalist id="cidades-datalist-perfil"></datalist>
              </div>
            </div>
            <div class="mb-3">
              <label class="mb-1 block text-sm font-medium">Endereço</label>
              <input name="address" value="<?= e($user['address'] ?? '') ?>" placeholder="Rua, número, bairro" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

        <!-- Redes sociais -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Redes sociais</h2>
          <?php if ($success === 'social'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['social']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="social">
            <div class="mb-3">
              <label class="mb-1 block text-sm font-medium">Site</label>
              <input name="website" value="<?= e($user['website'] ?? '') ?>" placeholder="https://..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <div class="mb-3 grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium">Instagram</label>
                <input name="instagram" value="<?= e($user['instagram'] ?? '') ?>" placeholder="@usuario" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">Facebook</label>
                <input name="facebook" value="<?= e($user['facebook'] ?? '') ?>" placeholder="facebook.com/usuario" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

        <!-- Profissional -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Profissional</h2>
          <?php if ($success === 'professional'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['professional']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="professional">
            <div class="mb-3 grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium">CRECI</label>
                <input name="creci" value="<?= e($user['creci'] ?? '') ?>" placeholder="Ex.: 12345-F" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium">CNPJ</label>
                <input name="cnpj" value="<?= e($user['cnpj'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              </div>
            </div>
            <div class="mb-3">
              <label class="mb-1 block text-sm font-medium">Cidade(s) de atuação</label>
              <input name="serviceArea" value="<?= e($user['service_area'] ?? '') ?>" placeholder="Ex.: São Paulo, Guarulhos e Osasco" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

        <!-- Sobre você -->
        <div class="rounded-2xl border border-brand-border bg-white p-5 shadow-sm sm:col-span-2">
          <h2 class="mb-4 text-sm font-semibold text-brand-text-secondary">Sobre você</h2>
          <?php if ($success === 'about'): ?><p class="mb-3 rounded-lg bg-brand-green/10 px-3 py-2 text-xs text-brand-green-hover"><?= e($cards['about']) ?></p><?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="about">
            <div class="mb-3">
              <textarea name="bio" rows="4" placeholder="Uma breve apresentação exibida no seu perfil público." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"><?= e($user['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar</button>
          </form>
        </div>

      </div>
    </main>
    <aside><?php render_account_nav('dados'); ?></aside>
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
