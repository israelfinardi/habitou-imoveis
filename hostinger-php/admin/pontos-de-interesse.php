<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$adminSuccess = $_SESSION['admin_success'] ?? null;
$adminError = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

$pois = db()->query('SELECT p.*, c.name AS city_name, c.state_code FROM points_of_interest p JOIN cities c ON c.id = p.city_id ORDER BY p.created_at DESC')->fetchAll();

$pageTitle = 'Pontos de interesse (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-1 text-2xl font-bold">Pontos de interesse</h1>
      <p class="mb-6 text-sm text-brand-text-secondary">Universidades, polos empresariais e outros locais estratégicos — alimentam o bloco "Perto de pontos de interesse" na home.</p>
      <?php if ($adminSuccess): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($adminSuccess) ?></p><?php endif; ?>
      <?php if ($adminError): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($adminError) ?></p><?php endif; ?>

      <div class="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Novo ponto de interesse</h2>
        <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <?= csrf_field() ?><input type="hidden" name="do" value="create_poi">
          <input name="name" required placeholder="Nome (ex.: USP, Shopping Iguatemi)" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          <select name="type" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
            <?php foreach (POI_TYPE_LABEL as $value => $label): ?>
              <option value="<?= e($value) ?>"><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="cityLabel" required class="js-city-picker rounded-lg border border-brand-border px-3 py-2 text-sm"
                 list="cidades-datalist-poi" autocomplete="off" placeholder="Cidade...">
          <datalist id="cidades-datalist-poi"></datalist>
          <div class="grid grid-cols-2 gap-3">
            <input name="latitude" required type="text" inputmode="decimal" placeholder="Latitude (ex.: -23.5505)" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
            <input name="longitude" required type="text" inputmode="decimal" placeholder="Longitude (ex.: -46.6333)" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          </div>
          <button type="submit" class="sm:col-span-2 rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Adicionar</button>
        </form>
        <p class="mt-2 text-xs text-brand-text-secondary">Dica: clique com o botão direito no local desejado no Google Maps para copiar as coordenadas.</p>
      </div>

      <?php if (empty($pois)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum ponto de interesse cadastrado ainda.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($pois as $poi): ?>
            <div class="rounded-xl border border-brand-border bg-white p-4">
              <span class="mb-2 inline-block rounded-full bg-brand-primary/10 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-brand-primary"><?= e(POI_TYPE_LABEL[$poi['type']] ?? $poi['type']) ?></span>
              <p class="font-semibold"><?= e($poi['name']) ?></p>
              <p class="text-xs text-brand-text-secondary"><?= e($poi['city_name']) ?> — <?= e($poi['state_code']) ?></p>
              <p class="mt-1 text-xs text-brand-text-secondary"><?= e($poi['latitude']) ?>, <?= e($poi['longitude']) ?></p>
              <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="mt-3" onsubmit="return confirm('Excluir este ponto de interesse?');">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $poi['id'] ?>"><input type="hidden" name="do" value="delete_poi">
                <button class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('pontos_interesse'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
