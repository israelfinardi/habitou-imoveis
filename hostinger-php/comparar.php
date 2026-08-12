<?php
require_once __DIR__ . '/includes/bootstrap.php';

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$properties = [];
if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT p.*, c.name AS city_name, c.state_code, n.name AS neighborhood_name
        FROM properties p JOIN cities c ON c.id = p.city_id LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
        WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['features'] = json_decode_safe($row['features']);
        $byId[$row['id']] = $row;
    }
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $properties[] = $byId[$id];
        }
    }
    $imgStmt = db()->prepare('SELECT url FROM property_images WHERE property_id = ? ORDER BY `order` LIMIT 1');
    foreach ($properties as &$p) {
        $imgStmt->execute([$p['id']]);
        $p['image_url'] = $imgStmt->fetchColumn() ?: null;
    }
    unset($p);
}

$rows = [
    'Preço' => fn($p) => format_currency_brl($p['listing_type'] === 'RENT' ? $p['price_rent'] : $p['price_sale']),
    'Tipo' => fn($p) => PROPERTY_TYPE_LABEL[$p['property_type']],
    'Cidade / bairro' => fn($p) => ($p['neighborhood_name'] ? $p['neighborhood_name'] . ', ' : '') . $p['city_name'],
    'Área total' => fn($p) => $p['total_area'] ? format_area($p['total_area']) : '—',
    'Quartos' => fn($p) => $p['bedrooms'] ?? '—',
    'Suítes' => fn($p) => $p['suites'] ?? '—',
    'Banheiros' => fn($p) => $p['bathrooms'] ?? '—',
    'Vagas' => fn($p) => $p['parking_spaces'] ?? '—',
    'Condomínio' => fn($p) => $p['condo_fee'] ? format_currency_brl($p['condo_fee']) : '—',
    'IPTU' => fn($p) => $p['iptu'] ? format_currency_brl($p['iptu']) : '—',
    'Características' => fn($p) => $p['features'] ? implode(', ', $p['features']) : '—',
];

$pageTitle = 'Comparar imóveis';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <h1 class="mb-6 text-2xl font-bold">Comparar imóveis</h1>
  <?php if (empty($properties)): ?>
    <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">
      Nenhum imóvel selecionado. Use o botão "Adicionar à comparação" nas páginas de imóveis.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full min-w-[640px] border-collapse text-sm">
        <thead><tr>
          <th class="w-40 p-3 text-left text-xs uppercase text-brand-text-secondary">Imóvel</th>
          <?php foreach ($properties as $p): ?>
            <th class="p-3 text-left">
              <a href="<?= e(property_href($p)) ?>">
                <div class="relative mb-2 h-28 w-full overflow-hidden rounded-lg bg-brand-bg-subtle">
                  <?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
                </div>
                <span class="line-clamp-2 text-sm font-semibold hover:text-brand-primary"><?= e($p['title']) ?></span>
              </a>
            </th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody class="divide-y divide-brand-border">
          <?php foreach ($rows as $label => $render): ?>
            <tr>
              <td class="p-3 text-xs font-semibold uppercase text-brand-text-secondary"><?= e($label) ?></td>
              <?php foreach ($properties as $p): ?><td class="p-3"><?= e((string) $render($p)) ?></td><?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
