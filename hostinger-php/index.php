<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$featured = get_featured_properties(6);
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$florianopolis = get_properties_by_city('florianopolis', 3);
$blumenau = get_properties_by_city('blumenau', 3);

$pageTitle = APP_NAME . ' — Apartamentos, casas e terrenos em Santa Catarina';
$pageDescription = 'Encontre apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina. Anuncie seu imóvel ou encontre imobiliárias e corretores de confiança.';
require __DIR__ . '/includes/header.php';
?>

<section class="border-b border-brand-border bg-white py-10 sm:py-14">
  <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
    <h1 class="text-2xl font-extrabold tracking-tight text-brand-text sm:text-4xl">Encontre o seu imóvel em Santa Catarina</h1>
    <p class="mx-auto mt-3 max-w-xl text-brand-text-secondary">26 anos conectando pessoas aos melhores apartamentos, casas e terrenos do estado.</p>

    <form action="<?= base_url('imoveis.php') ?>" method="get" class="mx-auto mt-8 flex max-w-3xl flex-col gap-3 rounded-2xl border border-brand-border bg-white p-4 shadow-lg sm:flex-row lg:hidden">
      <select name="transacao" class="rounded-xl border border-brand-border px-3 py-2.5 text-sm">
        <option value="comprar">Comprar</option>
        <option value="alugar">Alugar</option>
      </select>
      <select name="cidade" class="rounded-xl border border-brand-border px-3 py-2.5 text-sm">
        <option value="">Todas as cidades</option>
        <?php foreach (FEATURED_CITIES as $c): ?>
          <option value="<?= e($c['slug']) ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="tipo" class="rounded-xl border border-brand-border px-3 py-2.5 text-sm">
        <option value="">Qualquer tipo</option>
        <?php foreach (PROPERTY_TYPE_SLUG as $type => $slug): ?>
          <option value="<?= e($slug) ?>"><?= e(PROPERTY_TYPE_LABEL[$type]) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Buscar imóveis</button>
    </form>
  </div>
</section>
<?php render_category_pills(); ?>

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
  <h2 class="mb-2 text-xl font-bold">Cidades em destaque</h2>
  <div class="mb-10 flex flex-wrap gap-2">
    <?php foreach (FEATURED_CITIES as $c): ?>
      <a href="<?= base_url('cidade.php?slug=' . $c['slug']) ?>" class="rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary hover:text-brand-primary"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="mb-4 flex items-center justify-between">
    <h2 class="text-xl font-bold">Imóveis em destaque</h2>
    <a href="<?= base_url('imoveis.php') ?>" class="text-sm font-medium text-brand-primary hover:underline">Ver todos</a>
  </div>
  <?php render_property_grid($featured, $favoriteIds); ?>

  <?php if ($florianopolis): ?>
    <div class="mt-12">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">Imóveis em Florianópolis</h2>
        <a href="<?= base_url('cidade.php?slug=florianopolis') ?>" class="text-sm font-medium text-brand-primary hover:underline">Ver mais</a>
      </div>
      <?php render_property_grid($florianopolis, $favoriteIds); ?>
    </div>
  <?php endif; ?>

  <?php if ($blumenau): ?>
    <div class="mt-12">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold">Imóveis em Blumenau</h2>
        <a href="<?= base_url('cidade.php?slug=blumenau') ?>" class="text-sm font-medium text-brand-primary hover:underline">Ver mais</a>
      </div>
      <?php render_property_grid($blumenau, $favoriteIds); ?>
    </div>
  <?php endif; ?>
</section>

<section class="bg-brand-bg-subtle py-12">
  <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
    <h2 class="text-xl font-bold">Tem um imóvel para anunciar?</h2>
    <p class="mt-2 text-brand-text-secondary">Publique gratuitamente e alcance milhares de interessados em Santa Catarina.</p>
    <a href="<?= base_url('anunciante/novo.php') ?>" class="mt-5 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Anunciar imóvel</a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
