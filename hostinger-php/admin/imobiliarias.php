<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$agencies = db()->query('SELECT a.*, (SELECT COUNT(*) FROM properties p WHERE p.agency_id=a.id) AS pc, (SELECT COUNT(*) FROM users u WHERE u.agency_id=a.id) AS uc FROM agencies a ORDER BY name')->fetchAll();
$statuses = ['ACTIVE', 'INACTIVE', 'PENDING'];

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('imobiliarias.csv', [
        'name' => 'Nome', 'properties' => 'Imóveis', 'users' => 'Usuários', 'status' => 'Status',
    ], array_map(fn($a) => [
        'name' => $a['name'], 'properties' => $a['pc'], 'users' => $a['uc'], 'status' => $a['status'],
    ], $agencies));
}

$pageTitle = 'Imobiliárias (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Imobiliárias (<?= count($agencies) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if (empty($agencies)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhuma imobiliária cadastrada.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($agencies as $a): ?>
            <div class="rounded-2xl border border-brand-border bg-white p-4">
              <a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="mb-2 block truncate text-sm font-semibold text-brand-text hover:text-brand-primary"><?= e($a['name']) ?></a>
              <p class="mb-3 flex gap-3 text-xs text-brand-text-secondary">
                <span><?= $a['pc'] ?> imóve<?= $a['pc'] === 1 ? 'l' : 'is' ?></span>
                <span><?= $a['uc'] ?> usuário<?= $a['uc'] === 1 ? '' : 's' ?></span>
              </p>
              <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                <?= csrf_field() ?><input type="hidden" name="do" value="update_agency_status"><input type="hidden" name="id" value="<?= $a['id'] ?>">
                <select name="status" class="w-full rounded-lg border border-brand-border px-2 py-1.5 text-xs"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('imobiliarias'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
