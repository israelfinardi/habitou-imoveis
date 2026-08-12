<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT u.*, a.name AS agency_name, a.slug AS agency_slug FROM users u LEFT JOIN agencies a ON a.id = u.agency_id
    WHERE u.id = ? AND u.role IN ('AGENT','AGENCY_ADMIN') AND u.status = 'ACTIVE'");
$stmt->execute([$id]);
$agent = $stmt->fetch();
if (!$agent) {
    http_response_code(404);
    $pageTitle = 'Corretor não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Corretor não encontrado</h1></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$result = list_properties([], ['p.agent_id = ?' => $id]);

$pageTitle = $agent['first_name'] . ' ' . $agent['last_name'] . ' — Corretor';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
  <div class="mb-8 flex items-center gap-4">
    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-primary text-xl font-semibold text-white"><?= e(mb_strtoupper(mb_substr($agent['first_name'], 0, 1))) ?></span>
    <div>
      <h1 class="text-2xl font-bold"><?= e($agent['first_name'] . ' ' . $agent['last_name']) ?></h1>
      <?php if ($agent['creci']): ?><p class="text-sm text-brand-text-secondary">CRECI <?= e($agent['creci']) ?></p><?php endif; ?>
      <?php if ($agent['agency_name']): ?><a href="<?= base_url('imobiliaria.php?slug=' . $agent['agency_slug']) ?>" class="text-sm text-brand-primary hover:underline"><?= e($agent['agency_name']) ?></a><?php endif; ?>
    </div>
  </div>
  <h2 class="mb-4 text-lg font-bold">Imóveis deste corretor (<?= $result['total'] ?>)</h2>
  <?php render_property_grid($result['items'], $favoriteIds); ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
