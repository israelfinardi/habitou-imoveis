<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
if (!in_array($user['role'], ['AGENCY_ADMIN', 'ADMIN'], true) || !$user['agency_id']) {
    http_response_code(403);
    exit('Acesso negado.');
}

$stmt = db()->prepare('SELECT * FROM agencies WHERE id = ?');
$stmt->execute([$user['agency_id']]);
$agency = $stmt->fetch();
if (!$agency) {
    http_response_code(404);
    exit('Imobiliária não encontrada.');
}

$success = null;
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $serviceArea = trim($_POST['serviceArea'] ?? '');

    if (mb_strlen($name) < 2) {
        $fieldErrors['name'] = 'Informe o nome da imobiliária.';
    }

    if (empty($fieldErrors)) {
        db()->prepare('UPDATE agencies SET name=?, description=?, phone=?, whatsapp=?, email=?, website=?, cnpj=?, address=?, zip_code=?, city=?, state=?, service_area=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$name, $description ?: null, $phone ?: null, $whatsapp ?: null, $email ?: null, $website ?: null, $cnpj ?: null, $address ?: null, $zipCode ?: null, $city ?: null, $state ?: null, $serviceArea ?: null, $agency['id']]);
        $stmt->execute([$user['agency_id']]);
        $agency = $stmt->fetch();
        $success = 'Perfil da imobiliária atualizado com sucesso.';
    }
}

$pageTitle = 'Perfil da imobiliária';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <p class="mb-3 text-xs font-semibold uppercase text-brand-text-secondary">Painel da imobiliária</p>
      <nav class="flex flex-col gap-1">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Imóveis</a>
        <a href="<?= base_url('imobiliaria/feeds.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Feeds VRSync</a>
        <a href="<?= base_url('contratos.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Contratos</a>
        <a href="<?= base_url('imobiliaria/perfil.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle">Perfil</a>
      </nav>
    </aside>
    <main>
      <h1 class="mb-1 text-2xl font-bold">Perfil da imobiliária</h1>
      <p class="mb-6 text-sm text-brand-text-secondary">Essas informações aparecem na página pública da imobiliária e nos anúncios dos seus corretores.</p>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>

      <div class="mb-6 flex items-center gap-4">
        <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
          <img id="logo-preview" src="<?= e($agency['logo_url'] ?: '') ?>" class="<?= $agency['logo_url'] ? '' : 'hidden' ?> h-full w-full object-cover" alt="">
          <span id="logo-preview-fallback" class="<?= $agency['logo_url'] ? 'hidden' : '' ?> flex h-full w-full items-center justify-center text-2xl font-semibold text-brand-primary"><?= e(mb_strtoupper(mb_substr($agency['name'], 0, 1))) ?></span>
        </div>
        <div>
          <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">
            <span id="logo-upload-label">Alterar logo</span>
            <input type="file" id="logo-input" accept="image/jpeg,image/png,image/webp" class="hidden">
          </label>
          <p class="mt-1 text-xs text-brand-text-secondary">JPG, PNG ou WEBP, até 8MB.</p>
        </div>
      </div>

      <form method="post" class="max-w-2xl">
        <?= csrf_field() ?>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Nome da imobiliária</label>
          <input name="name" required value="<?= e($agency['name']) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Descrição</label>
          <textarea name="description" rows="4" placeholder="Uma breve apresentação exibida no perfil público." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"><?= e($agency['description'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Telefone</label><input name="phone" value="<?= e($agency['phone'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">WhatsApp</label><input name="whatsapp" value="<?= e($agency['whatsapp'] ?? '') ?>" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">E-mail</label><input type="email" name="email" value="<?= e($agency['email'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Site</label><input name="website" value="<?= e($agency['website'] ?? '') ?>" placeholder="https://..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">CNPJ</label><input name="cnpj" value="<?= e($agency['cnpj'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        </div>
        <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Endereço</label><input name="address" value="<?= e($agency['address'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">CEP</label><input name="zipCode" value="<?= e($agency['zip_code'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Cidade (sede)</label><input name="city" value="<?= e($agency['city'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="mb-4"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Estado</label><input name="state" value="<?= e($agency['state'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
        </div>
        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium">Cidades de atuação</label>
          <input name="serviceArea" value="<?= e($agency['service_area'] ?? '') ?>" placeholder="Ex.: Florianópolis, São José e Palhoça" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar alterações</button>
      </form>
    </main>
  </div>
</div>
<script>
document.getElementById('logo-input').addEventListener('change', function (e) {
  const file = e.target.files[0];
  if (!file) return;
  const label = document.getElementById('logo-upload-label');
  label.textContent = 'Enviando...';
  const formData = new FormData();
  formData.append('logo', file);
  formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

  fetch(APP_BASE + 'actions/upload_agency_logo.php', { method: 'POST', body: formData })
    .then((r) => r.json())
    .then((data) => {
      label.textContent = 'Alterar logo';
      if (data.url) {
        const img = document.getElementById('logo-preview');
        img.src = data.url;
        img.classList.remove('hidden');
        const fallback = document.getElementById('logo-preview-fallback');
        if (fallback) fallback.classList.add('hidden');
      }
      if (data.error) alert(data.error);
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
