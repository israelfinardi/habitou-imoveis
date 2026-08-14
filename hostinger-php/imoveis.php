<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/filters_modal.php';
require_once __DIR__ . '/includes/property_mutations.php';

$params = $_GET;
if (!empty($params['cidade_nome']) && empty($params['cidade'])) {
    $params['cidade'] = resolve_city_slug($params['cidade_nome']) ?? '__nenhuma__';
}
$result = list_properties($params);
$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];

$pageTitle = 'Busca de imóveis';
$pageDescription = 'Busque apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="w-full px-4 py-8 sm:px-6 lg:px-8">
  <h1 class="mb-1 text-2xl font-bold">Busca de imóveis</h1>
  <p class="mb-6 text-sm text-brand-text-secondary"><?= $result['total'] ?> imóve<?= $result['total'] === 1 ? 'l encontrado' : 'is encontrados' ?></p>

  <div id="results-layout" class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-6">
    <div>
      <?php render_property_list($result['items'], $favoriteIds); ?>
      <?php
      $baseQuery = 'imoveis.php?' . http_build_query(array_diff_key($_GET, ['pagina' => '']));
      render_pagination($result['page'], $result['total_pages'], base_url($baseQuery));
      ?>
    </div>
    <div class="mt-6 lg:mt-0"><?php render_results_map($result['items']); ?></div>
  </div>
</div>
<?php render_filters_modal(base_url('imoveis.php'), $_GET); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
