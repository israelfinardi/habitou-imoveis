<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/filters_form.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM cities WHERE slug = ?');
$stmt->execute([$slug]);
$city = $stmt->fetch();

// As cidades em destaque são páginas de marketing curadas — devem sempre existir,
// mesmo que ainda não tenham nenhum imóvel cadastrado (ex.: banco recém-limpo).
if (!$city) {
    foreach (FEATURED_CITIES as $fc) {
        if ($fc['slug'] === $slug) {
            db()->prepare('INSERT INTO cities (name, slug, state, state_code, region, latitude, longitude) VALUES (?,?,?,?,?,?,?)')
                ->execute([$fc['name'], $fc['slug'], $fc['state'], $fc['state_code'], 'Santa Catarina', $fc['lat'], $fc['lng']]);
            $stmt->execute([$slug]);
            $city = $stmt->fetch();
            break;
        }
    }
}

if (!$city) {
    http_response_code(404);
    $pageTitle = 'Cidade não encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Cidade não encontrada</h1></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$hasFilters = !empty($_GET['transacao']) || !empty($_GET['tipo']) || !empty($_GET['bairro']) || !empty($_GET['q']);

$stmtN = db()->prepare('SELECT * FROM neighborhoods WHERE city_id = ? ORDER BY name');
$stmtN->execute([$city['id']]);
$neighborhoods = $stmtN->fetchAll();

$pageTitle = 'Imóveis em ' . $city['name'];
$pageDescription = $city['description'] ?: "Encontre apartamentos, casas e terrenos para comprar ou alugar em {$city['name']} - {$city['state_code']}.";
require __DIR__ . '/includes/header.php';

if ($hasFilters) {
    $params = $_GET;
    $params['cidade'] = $city['slug'];
    $result = list_properties($params);
    ?>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <nav class="mb-4 text-sm text-brand-text-secondary">
        <a href="<?= base_url('/') ?>" class="hover:text-brand-primary">Início</a> /
        <a href="<?= base_url('cidade.php?slug=' . $city['slug']) ?>" class="hover:text-brand-primary"><?= e($city['name']) ?></a>
      </nav>
      <h1 class="mb-4 text-2xl font-bold">Imóveis em <?= e($city['name']) ?></h1>
      <p class="mb-4 text-sm text-brand-text-secondary"><?= $result['total'] ?> imóve<?= $result['total'] === 1 ? 'l encontrado' : 'is encontrados' ?></p>
      <div class="mb-6"><?php render_filters_form(base_url('cidade.php?slug=' . $city['slug']), $_GET, false, $neighborhoods); ?></div>
      <?php render_property_results($result['items'], $favoriteIds); ?>
      <?php
      $baseQuery = 'cidade.php?' . http_build_query(array_diff_key($_GET, ['pagina' => '']));
      render_pagination($result['page'], $result['total_pages'], base_url($baseQuery));
      ?>
    </div>
    <?php
} else {
    $properties = get_properties_by_city($city['slug'], 6);
    ?>
    <div class="bg-brand-bg-subtle">
      <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Imóveis em <?= e($city['name']) ?></h1>
        <p class="mt-2 max-w-2xl text-brand-text-secondary">
          <?= e($city['description'] ?: "Explore os melhores apartamentos, casas e terrenos disponíveis para comprar ou alugar em {$city['name']}, Santa Catarina.") ?>
        </p>
        <div class="mt-6 flex gap-3">
          <a href="<?= base_url('cidade.php?slug=' . $city['slug'] . '&transacao=comprar') ?>" class="rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Comprar</a>
          <a href="<?= base_url('cidade.php?slug=' . $city['slug'] . '&transacao=alugar') ?>" class="rounded-full border border-brand-border bg-white px-5 py-2.5 text-sm font-semibold hover:border-brand-primary">Alugar</a>
        </div>
      </div>
    </div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <h2 class="mb-4 text-xl font-bold">Tipos de imóvel em <?= e($city['name']) ?></h2>
      <div class="mb-10 flex flex-wrap gap-2">
        <?php foreach (PROPERTY_TYPE_SLUG as $type => $slugType): ?>
          <a href="<?= base_url('cidade.php?slug=' . $city['slug'] . '&transacao=comprar&tipo=' . $slugType) ?>" class="rounded-full border border-brand-border px-3 py-1.5 text-sm hover:border-brand-primary hover:text-brand-primary"><?= e(PROPERTY_TYPE_LABEL[$type]) ?></a>
        <?php endforeach; ?>
      </div>
      <h2 class="mb-4 text-xl font-bold">Imóveis recentes</h2>
      <?php render_property_grid($properties, $favoriteIds); ?>
    </div>
    <?php
}
require __DIR__ . '/includes/footer.php';
