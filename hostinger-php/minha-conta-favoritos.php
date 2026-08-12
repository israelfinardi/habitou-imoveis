<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = require_login();
$stmt = db()->prepare('SELECT p.id FROM favorites f JOIN properties p ON p.id = f.property_id WHERE f.user_id = ? ORDER BY f.created_at DESC');
$stmt->execute([$user['id']]);
$ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

$properties = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . " WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[$row['id']] = $row;
    }
    foreach ($ids as $id) {
        if (isset($byId[$id])) $properties[] = $byId[$id];
    }
    $properties = attach_primary_images(db(), $properties);
}

$pageTitle = 'Favoritos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside><?php render_account_nav('favoritos'); ?></aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Meus favoritos</h1>
      <?php render_property_grid($properties, $ids, 'Você ainda não favoritou nenhum imóvel.'); ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
