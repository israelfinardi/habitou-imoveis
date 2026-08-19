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
$pageDescription = 'Busque apartamentos, casas e terrenos para comprar ou alugar em todo o Brasil.';
require __DIR__ . '/includes/header.php';
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8">
  <h1 class="sr-only">Busca de imóveis</h1>

  <div id="results-layout" class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-6">
    <div id="results-list-col">
      <div id="sheet-handle-wrap" class="sticky top-0 z-10 -mx-4 mb-3 bg-white px-4 pb-2 pt-1 lg:hidden">
        <button type="button" id="sheet-drag-handle" class="mx-auto block h-1.5 w-10 rounded-full bg-brand-border" aria-label="Arrastar lista"></button>
        <p id="sheet-count-text" class="mt-2 text-center text-sm font-semibold text-brand-text">
          <?= count($result['items']) ?> imóve<?= count($result['items']) === 1 ? 'l' : 'is' ?> nesta área
        </p>
      </div>
      <?php render_property_list($result['items'], $favoriteIds); ?>
      <?php
      $baseQuery = 'imoveis.php?' . http_build_query(array_diff_key($_GET, ['pagina' => '']));
      render_pagination($result['page'], $result['total_pages'], base_url($baseQuery));
      ?>
    </div>
    <div class="mt-6 lg:mt-0"><?php render_results_map($result['items'], $favoriteIds); ?></div>
  </div>
</div>
<?php render_filters_modal(base_url('imoveis.php'), $_GET); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
