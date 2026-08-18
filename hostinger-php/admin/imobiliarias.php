<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$agencies = db()->query('SELECT a.*, (SELECT COUNT(*) FROM properties p WHERE p.agency_id=a.id) AS pc, (SELECT COUNT(*) FROM users u WHERE u.agency_id=a.id) AS uc, (SELECT COUNT(*) FROM feeds f WHERE f.agency_id=a.id) AS fc FROM agencies a ORDER BY name')->fetchAll();
$statuses = ['ACTIVE', 'INACTIVE', 'PENDING'];

$pageTitle = 'Imobiliárias (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Imobiliárias (<?= count($agencies) ?>)</h1>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Imóveis</th><th class="px-4 py-3">Usuários</th><th class="px-4 py-3">Feeds</th><th class="px-4 py-3">Status</th></tr>
          </thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($agencies as $a): ?>
              <tr>
                <td class="px-4 py-3"><a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="font-medium hover:text-brand-primary"><?= e($a['name']) ?></a></td>
                <td class="px-4 py-3 text-xs"><?= $a['pc'] ?></td>
                <td class="px-4 py-3 text-xs"><?= $a['uc'] ?></td>
                <td class="px-4 py-3 text-xs"><?= $a['fc'] ?></td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                    <?= csrf_field() ?><input type="hidden" name="do" value="update_agency_status"><input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <select name="status" class="rounded-lg border border-brand-border px-2 py-1 text-xs"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <aside><?php render_admin_nav('imobiliarias'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
