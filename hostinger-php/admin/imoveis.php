<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$q = trim($_GET['q'] ?? '');
$pdo = db();
$sql = "SELECT p.*, c.name AS city_name, ag.name AS agency_name, u.first_name, u.last_name
        FROM properties p JOIN cities c ON c.id = p.city_id LEFT JOIN agencies ag ON ag.id = p.agency_id
        JOIN users u ON u.id = p.advertiser_id";
$args = [];
if ($q) {
    $sql .= ' WHERE p.title LIKE ?';
    $args[] = "%$q%";
}
$sql .= ' ORDER BY p.created_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$properties = $stmt->fetchAll();
$statuses = ['DRAFT', 'PUBLISHED', 'PAUSED', 'ARCHIVED'];
$featuredCount = (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE is_featured = 1')->fetchColumn();
$adminError = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

$pageTitle = 'Imóveis (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside><?php render_admin_nav('imoveis'); ?></aside>
    <main>
      <h1 class="mb-1 text-2xl font-bold">Imóveis (<?= count($properties) ?>)</h1>
      <p class="mb-4 text-sm text-brand-text-secondary">Destaques na home: <?= $featuredCount ?> / <?= FEATURED_PROPERTIES_LIMIT ?></p>
      <?php if ($adminError): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($adminError) ?></p><?php endif; ?>
      <form class="mb-4 max-w-sm"><input name="q" value="<?= e($q) ?>" placeholder="Buscar por título" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></form>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr><th class="px-4 py-3">Título</th><th class="px-4 py-3">Origem</th><th class="px-4 py-3">Anunciante</th><th class="px-4 py-3">Preço</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Destaque</th><th class="px-4 py-3">Ações</th></tr>
          </thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($properties as $p): ?>
              <tr>
                <td class="px-4 py-3"><p class="font-medium"><?= e($p['title']) ?></p><p class="text-xs text-brand-text-secondary"><?= e($p['code']) ?> · <?= e($p['city_name']) ?></p></td>
                <td class="px-4 py-3 text-xs"><?= e($p['origin']) ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= e($p['agency_name'] ?? ($p['first_name'] . ' ' . $p['last_name'])) ?></td>
                <td class="px-4 py-3 text-xs"><?= format_currency_brl($p['price_sale'] ?? $p['price_rent']) ?></td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                    <?= csrf_field() ?><input type="hidden" name="do" value="update_property_status"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <select name="status" class="rounded-lg border border-brand-border px-2 py-1 text-xs"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
                  </form>
                </td>
                <td class="px-4 py-3">
                  <?php if ($p['status'] !== 'PUBLISHED' && !$p['is_featured']): ?>
                    <span class="text-xs text-brand-text-secondary" title="Só imóveis publicados podem ser destaque">—</span>
                  <?php else: ?>
                    <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                      <?= csrf_field() ?><input type="hidden" name="do" value="toggle_featured"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs">
                        <input type="checkbox" name="featured" value="1" <?= $p['is_featured'] ? 'checked' : '' ?>>
                        Destaque
                      </label>
                    </form>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onsubmit="return confirm('Excluir permanentemente?');">
                    <?= csrf_field() ?><input type="hidden" name="do" value="delete_property"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button class="text-xs text-red-600 hover:underline">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
