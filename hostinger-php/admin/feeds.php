<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$feeds = db()->query('SELECT f.*, a.name AS agency_name, (SELECT COUNT(*) FROM properties p WHERE p.source_feed_id=f.id) AS pc FROM feeds f JOIN agencies a ON a.id=f.agency_id ORDER BY f.created_at DESC')->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('feeds.csv', [
        'name' => 'Feed', 'agency_name' => 'Imobiliária', 'status' => 'Status', 'last_sync_at' => 'Última sincronização', 'properties' => 'Imóveis',
    ], array_map(fn($f) => [
        'name' => $f['name'], 'agency_name' => $f['agency_name'], 'status' => $f['status'],
        'last_sync_at' => $f['last_sync_at'] ?: 'Nunca', 'properties' => $f['pc'],
    ], $feeds));
}

$pageTitle = 'Feeds VRSync (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Feeds VRSync (<?= count($feeds) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if (empty($feeds)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum feed cadastrado.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($feeds as $f): ?>
            <a href="<?= base_url('imobiliaria/feed.php?id=' . $f['id']) ?>" class="block rounded-2xl border border-brand-border bg-white p-4 hover:border-brand-primary">
              <p class="mb-1 truncate text-sm font-semibold text-brand-text"><?= e($f['name']) ?></p>
              <p class="mb-3 truncate text-xs text-brand-text-secondary"><?= e($f['agency_name']) ?></p>
              <p class="mb-1 text-xs"><span class="rounded-full bg-brand-bg-subtle px-2 py-1 font-medium"><?= e($f['status']) ?></span></p>
              <p class="mt-2 text-xs text-brand-text-secondary"><?= $f['pc'] ?> imóve<?= $f['pc'] === 1 ? 'l' : 'is' ?> · última sinc.: <?= $f['last_sync_at'] ? format_date($f['last_sync_at']) : 'nunca' ?></p>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('feeds'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
