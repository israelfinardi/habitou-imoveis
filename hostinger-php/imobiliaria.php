<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM agencies WHERE slug = ?');
$stmt->execute([$slug]);
$agency = $stmt->fetch();
if (!$agency) {
    http_response_code(404);
    $pageTitle = 'Imobiliária não encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Imobiliária não encontrada</h1></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$params = $_GET;
unset($params['slug']);
$result = list_properties($params, ['p.agency_id = ?' => $agency['id']]);

$agentsStmt = db()->prepare("SELECT id, first_name, last_name, creci FROM users WHERE agency_id = ? AND role IN ('AGENT','AGENCY_ADMIN') AND status = 'ACTIVE' ORDER BY first_name");
$agentsStmt->execute([$agency['id']]);
$agents = $agentsStmt->fetchAll();

$pageTitle = $agency['name'];
$pageDescription = $agency['description'] ?: "Imóveis anunciados por {$agency['name']} no Habitou Imóveis.";
require __DIR__ . '/includes/header.php';
?>
<div class="border-b border-brand-border bg-brand-bg-subtle">
  <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 lg:px-8">
    <div class="relative h-20 w-20 overflow-hidden rounded-full bg-white shadow">
      <?php if ($agency['logo_url']): ?><img src="<?= e($agency['logo_url']) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
    </div>
    <h1 class="text-2xl font-bold"><?= e($agency['name']) ?></h1>
    <?php if ($agency['description']): ?><p class="max-w-2xl text-sm text-brand-text-secondary"><?= e($agency['description']) ?></p><?php endif; ?>
    <?php if (!empty($agency['service_area'])): ?><p class="text-sm text-brand-text-secondary">Atua em: <?= e($agency['service_area']) ?></p><?php endif; ?>
    <div class="flex flex-wrap justify-center gap-4 text-sm text-brand-text-secondary">
      <?php if ($agency['phone']): ?><span><?= e($agency['phone']) ?></span><?php endif; ?>
      <?php if ($agency['email']): ?><span><?= e($agency['email']) ?></span><?php endif; ?>
      <?php if ($agency['city']): ?><span><?= e($agency['city']) ?><?= $agency['state'] ? ' — ' . e($agency['state']) : '' ?></span><?php endif; ?>
      <?php if ($agency['website']): ?><a href="<?= e($agency['website']) ?>" target="_blank" rel="noopener noreferrer" class="text-brand-primary hover:underline"><?= e($agency['website']) ?></a><?php endif; ?>
    </div>
    <?php if (!empty($agency['whatsapp'])): ?>
      <a href="https://wa.me/55<?= e(preg_replace('/\D/', '', $agency['whatsapp'])) ?>" target="_blank" rel="noopener noreferrer" class="rounded-full bg-brand-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-green-hover">Conversar no WhatsApp</a>
    <?php endif; ?>
  </div>
</div>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
  <?php if ($agents): ?>
    <div class="mb-10">
      <h2 class="mb-3 text-lg font-bold">Corretores</h2>
      <div class="flex flex-wrap gap-3">
        <?php foreach ($agents as $agent): ?>
          <a href="<?= base_url('corretor.php?id=' . $agent['id']) ?>" class="flex items-center gap-2 rounded-full border border-brand-border px-3 py-2 text-sm hover:border-brand-primary">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white"><?= e(mb_strtoupper(mb_substr($agent['first_name'], 0, 1))) ?></span>
            <?= e($agent['first_name'] . ' ' . $agent['last_name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <h2 class="mb-4 text-lg font-bold">Imóveis publicados (<?= $result['total'] ?>)</h2>
  <?php render_property_grid($result['items'], $favoriteIds); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
