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

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('favoritos.csv', [
        'title' => 'Título', 'city_name' => 'Cidade', 'listing_type' => 'Transação',
        'property_type' => 'Tipo', 'price_sale' => 'Preço venda', 'price_rent' => 'Preço aluguel',
    ], array_map(fn($p) => [
        'title' => $p['title'], 'city_name' => $p['city_name'], 'listing_type' => LISTING_TYPE_LABEL[$p['listing_type']] ?? $p['listing_type'],
        'property_type' => PROPERTY_TYPE_LABEL[$p['property_type']] ?? $p['property_type'], 'price_sale' => $p['price_sale'], 'price_rent' => $p['price_rent'],
    ], $properties));
}

$pageTitle = 'Favoritos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Meus favoritos</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php render_property_grid($properties, $ids, 'Você ainda não favoritou nenhum imóvel.'); ?>
    </main>
    <aside><?php render_account_nav('favoritos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
