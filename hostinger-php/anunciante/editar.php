<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_form.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);
$property = get_property_by_id($id);
if (!$property || !can_manage_property($user, $property)) {
    http_response_code(404);
    exit('Imóvel não encontrado.');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $input = parse_property_form($_POST);
    if (mb_strlen($input['title']) < 10) {
        $error = 'O título deve ter pelo menos 10 caracteres.';
    } elseif (!$input['cidade'] || !$input['bairro']) {
        $error = 'Selecione a cidade e informe o bairro.';
    } else {
        try {
            update_property($id, $input, $user);
            $property = get_property_by_id($id);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$cityStmt = db()->prepare('SELECT name, state_code FROM cities WHERE id = ?');
$cityStmt->execute([$property['city_id']]);
$cityRow = $cityStmt->fetch();
$property['city_label'] = $cityRow ? $cityRow['name'] . ' (' . $cityRow['state_code'] . ')' : '';
if ($property['neighborhood_id']) {
    $nStmt = db()->prepare('SELECT name FROM neighborhoods WHERE id = ?');
    $nStmt->execute([$property['neighborhood_id']]);
    $property['neighborhood_name'] = $nStmt->fetchColumn();
}

$imgStmt = db()->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY `order`');
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

$pageTitle = 'Editar imóvel';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <nav class="flex flex-col gap-1">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Meus imóveis</a>
        <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">+ Novo imóvel</a>
      </nav>
    </aside>
    <main>
      <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Editar imóvel</h1>
        <div class="flex items-center gap-2">
          <span class="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs font-semibold"><?= e(PROPERTY_STATUS_LABEL[$property['status']]) ?></span>
          <?php if ($property['status'] !== 'PUBLISHED'): ?>
            <form method="post" action="<?= base_url('actions/property_action.php') ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="publish">
              <button class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Publicar agora</button>
            </form>
          <?php else: ?>
            <form method="post" action="<?= base_url('actions/property_action.php') ?>">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="do" value="pause">
              <button class="rounded-full border border-brand-border px-4 py-2 text-sm font-semibold hover:border-brand-primary">Pausar anúncio</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <p class="mb-6 text-sm text-brand-text-secondary">Código <?= e($property['code']) ?></p>
      <?php if (!empty($_GET['criado'])): ?>
        <p class="mb-4 rounded-lg bg-brand-bg-subtle px-3 py-2 text-sm text-brand-text-secondary">Imóvel criado como <strong>rascunho</strong> — ele só aparece na busca e nos filtros depois de publicado. Adicione fotos e clique em <strong>Publicar agora</strong> quando estiver pronto.</p>
      <?php endif; ?>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

      <section class="mb-8">
        <h2 class="mb-3 text-lg font-bold">Fotos</h2>
        <div id="photo-list" class="mb-4 flex flex-wrap gap-3">
          <?php foreach ($images as $img): ?>
            <div class="group relative h-28 w-40 shrink-0 overflow-hidden rounded-lg border border-brand-border" data-image-id="<?= $img['id'] ?>">
              <img src="<?= e($img['url']) ?>" class="h-full w-full object-cover" alt="">
              <?php if ($img['is_primary']): ?><span class="absolute left-1 top-1 rounded bg-brand-primary px-1.5 py-0.5 text-[10px] font-semibold text-white">Principal</span><?php endif; ?>
              <div class="absolute inset-x-0 bottom-0 flex justify-between gap-1 bg-black/60 p-1 opacity-0 transition group-hover:opacity-100">
                <?php if (!$img['is_primary']): ?><button type="button" class="js-set-primary text-[10px] text-white hover:underline" data-id="<?= $img['id'] ?>">Tornar principal</button><?php endif; ?>
                <button type="button" class="js-remove-photo ml-auto text-[10px] text-white hover:underline" data-id="<?= $img['id'] ?>">Remover</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary">
          <span id="upload-label">+ Adicionar fotos</span>
          <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
        </label>
        <p class="mt-1 text-xs text-brand-text-secondary">JPG, PNG ou WEBP, até 8MB por foto.</p>
      </section>

      <form method="post">
        <?= csrf_field() ?>
        <?php render_property_form($property); ?>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar alterações</button>
      </form>
    </main>
  </div>
</div>

<script>
document.getElementById('photo-input').addEventListener('change', function (e) {
  const files = e.target.files;
  if (!files.length) return;
  const label = document.getElementById('upload-label');
  label.textContent = 'Enviando...';
  const formData = new FormData();
  formData.append('property_id', '<?= $id ?>');
  for (const f of files) formData.append('files[]', f);

  fetch(APP_BASE + 'actions/upload_foto.php', { method: 'POST', body: formData })
    .then((r) => r.json())
    .then((data) => {
      label.textContent = '+ Adicionar fotos';
      if (data.images && data.images.length) location.reload();
      if (data.errors && data.errors.length) alert(data.errors.join('\n'));
    });
});

document.addEventListener('click', function (e) {
  const removeBtn = e.target.closest('.js-remove-photo');
  if (removeBtn) {
    fetch(APP_BASE + 'actions/photo_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ do: 'remove', image_id: removeBtn.dataset.id }),
    }).then(() => location.reload());
  }
  const primaryBtn = e.target.closest('.js-set-primary');
  if (primaryBtn) {
    fetch(APP_BASE + 'actions/photo_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ do: 'primary', image_id: primaryBtn.dataset.id }),
    }).then(() => location.reload());
  }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
