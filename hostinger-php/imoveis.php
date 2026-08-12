<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/filters_form.php';

$result = list_properties($_GET);
$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];

$pageTitle = 'Busca de imóveis';
$pageDescription = 'Busque apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <h1 class="mb-1 text-2xl font-bold">Busca de imóveis</h1>
  <p class="mb-6 text-sm text-brand-text-secondary"><?= $result['total'] ?> imóve<?= $result['total'] === 1 ? 'l encontrado' : 'is encontrados' ?></p>

  <div class="mb-6"><?php render_filters_form(base_url('imoveis.php'), $_GET, true); ?></div>

  <?php render_property_grid($result['items'], $favoriteIds); ?>

  <?php
  $baseQuery = 'imoveis.php?' . http_build_query(array_diff_key($_GET, ['pagina' => '']));
  render_pagination($result['page'], $result['total_pages'], base_url($baseQuery));
  ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
