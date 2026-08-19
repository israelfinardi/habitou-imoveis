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
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary"><tr><th class="px-4 py-3">Feed</th><th class="px-4 py-3">Imobiliária</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Última sinc.</th><th class="px-4 py-3">Imóveis</th></tr></thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($feeds as $f): ?>
              <tr>
                <td class="px-4 py-3"><a href="<?= base_url('imobiliaria/feed.php?id=' . $f['id']) ?>" class="font-medium hover:text-brand-primary"><?= e($f['name']) ?></a></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= e($f['agency_name']) ?></td>
                <td class="px-4 py-3 text-xs"><?= e($f['status']) ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= $f['last_sync_at'] ? format_date($f['last_sync_at']) : 'Nunca' ?></td>
                <td class="px-4 py-3 text-xs"><?= $f['pc'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <aside><?php render_admin_nav('feeds'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
