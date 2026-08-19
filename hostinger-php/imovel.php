<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$property = get_property_by_slug($slug);
if (!$property || $property['status'] !== 'PUBLISHED') {
    http_response_code(404);
    $pageTitle = 'Imóvel não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Imóvel não encontrado</h1><p class="mt-2 text-brand-text-secondary">Ele pode ter sido removido ou pausado pelo anunciante.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$isFavorite = false;
if ($user) {
    $stmt = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND property_id = ?');
    $stmt->execute([$user['id'], $property['id']]);
    $isFavorite = (bool) $stmt->fetchColumn();
}
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$similar = get_similar_properties($property);

$price = $property['listing_type'] === 'RENT' ? $property['price_rent'] : $property['price_sale'];
$lat = $property['latitude'] ?: $property['city_lat'];
$lng = $property['longitude'] ?: $property['city_lng'];
$approximateLocation = !$property['latitude'] || !$property['longitude'];

$canonical = base_url('imovel.php?slug=' . $property['slug']);
$pageTitle = $property['title'];
$pageDescription = $property['description'] ? mb_substr(strip_tags($property['description']), 0, 155) : $property['title'];
require __DIR__ . '/includes/header.php';

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'RealEstateListing',
    'name' => $property['title'],
    'description' => $property['description'],
    'url' => $canonical,
    'image' => array_column($property['images'], 'url'),
    'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => $property['city_name'],
        'addressRegion' => $property['state_code'],
        'addressCountry' => 'BR',
        'streetAddress' => $property['street'],
        'postalCode' => $property['zip_code'],
    ],
];
if ($price) {
    $jsonLd['offers'] = ['@type' => 'Offer', 'price' => (float) $price, 'priceCurrency' => 'BRL'];
}
?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<div class="mx-auto max-w-[1800px] px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
  <nav class="mb-4 text-sm text-brand-text-secondary">
    <a href="<?= base_url('/') ?>" class="hover:text-brand-primary">Início</a> /
    <a href="<?= base_url('cidade.php?slug=' . $property['city_slug']) ?>" class="hover:text-brand-primary"><?= e($property['city_name']) ?></a> /
    <a href="<?= base_url('cidade.php?slug=' . $property['city_slug'] . '&transacao=' . LISTING_TYPE_SLUG[$property['listing_type']]) ?>" class="hover:text-brand-primary"><?= e(LISTING_TYPE_LABEL[$property['listing_type']]) ?></a> /
    <span><?= e($property['title']) ?></span>
  </nav>

  <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
    <div class="lg:col-span-3">
      <?php $images = $property['images']; render_property_gallery($images, $property['title']); ?>

      <div class="mt-6 flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold"><?= e($property['title']) ?></h1>
          <p class="mt-1 text-sm text-brand-text-secondary">
            <?= !empty($property['neighborhood_name']) ? e($property['neighborhood_name']) . ', ' : '' ?><?= e($property['city_name']) ?> — <?= e($property['state_code']) ?>
            <?= !empty($property['street']) ? ' · ' . e($property['street']) : '' ?>
          </p>
        </div>
        <button type="button" class="js-favorite-btn <?= $isFavorite ? 'is-favorite' : '' ?> flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white shadow ring-1 ring-brand-border" data-property-id="<?= (int) $property['id'] ?>" aria-label="Favoritar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="<?= $isFavorite ? '#C1502E' : 'none' ?>" stroke="<?= $isFavorite ? '#C1502E' : '#717171' ?>" stroke-width="1.8"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>
        </button>
      </div>

      <p class="mt-4 text-3xl font-bold text-brand-primary"><?= format_currency_brl($price) ?></p>
      <div class="mt-3">
        <button type="button" class="js-compare-toggle rounded-full border border-brand-border px-4 py-2.5 text-sm font-semibold hover:border-brand-primary" data-id="<?= (int) $property['id'] ?>" data-title="<?= e($property['title']) ?>" data-image="<?= e($images[0]['url'] ?? '') ?>">Adicionar à comparação</button>
      </div>
      <?php if ($property['condo_fee']): ?><p class="mt-2 text-sm text-brand-text-secondary">Condomínio: <?= format_currency_brl($property['condo_fee']) ?></p><?php endif; ?>
      <?php if ($property['iptu']): ?><p class="text-sm text-brand-text-secondary">IPTU: <?= format_currency_brl($property['iptu']) ?></p><?php endif; ?>

      <div class="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-brand-border p-4 sm:grid-cols-4">
        <div><p class="text-xs text-brand-text-secondary">Área total</p><p class="text-sm font-semibold"><?= $property['total_area'] ? format_area($property['total_area']) : '—' ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Quartos</p><p class="text-sm font-semibold"><?= $property['bedrooms'] ?? '—' ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Suítes</p><p class="text-sm font-semibold"><?= $property['suites'] ?? '—' ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Banheiros</p><p class="text-sm font-semibold"><?= $property['bathrooms'] ?? '—' ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Vagas</p><p class="text-sm font-semibold"><?= $property['parking_spaces'] ?? '—' ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Tipo</p><p class="text-sm font-semibold"><?= e(PROPERTY_TYPE_LABEL[$property['property_type']]) ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Código</p><p class="text-sm font-semibold"><?= e($property['code']) ?></p></div>
        <div><p class="text-xs text-brand-text-secondary">Publicado</p><p class="text-sm font-semibold"><?= format_date($property['published_at']) ?></p></div>
      </div>

      <?php if ($property['description']): ?>
        <div class="mt-6">
          <h2 class="mb-2 text-lg font-bold">Descrição</h2>
          <p id="description-text" class="js-clamp line-clamp-6 whitespace-pre-line text-sm leading-relaxed text-brand-text-secondary"><?= nl2br(e($property['description'])) ?></p>
          <button type="button" id="description-toggle" class="js-clamp-toggle mt-2 hidden text-sm font-semibold text-brand-text underline">Mostrar mais</button>
        </div>
      <?php endif; ?>

      <?php if (!empty($property['features'])): ?>
        <div class="mt-6">
          <h2 class="mb-2 text-lg font-bold">Características</h2>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($property['features'] as $f): ?>
              <span class="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs"><?= e($f) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($lat && $lng): ?>
        <div class="mt-6">
          <h2 class="mb-2 text-lg font-bold">Localização</h2>
          <?php if ($approximateLocation): ?>
            <p class="mb-2 text-xs text-brand-text-secondary">Localização aproximada (centro de <?= e($property['city_name']) ?>).</p>
          <?php endif; ?>
          <div id="map" class="h-72 w-full rounded-xl"></div>
          <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
          <script>
            var map = L.map('map').setView([<?= (float) $lat ?>, <?= (float) $lng ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            L.marker([<?= (float) $lat ?>, <?= (float) $lng ?>]).addTo(map).bindPopup(<?= json_encode($property['title'], JSON_UNESCAPED_UNICODE) ?>);
          </script>
        </div>
      <?php endif; ?>
    </div>

    <div class="lg:col-span-1">
      <div id="anunciante" class="sticky top-24 scroll-mt-24 rounded-xl border border-brand-border bg-white p-5">
        <?php
        $isAgent = !empty($property['agent_first_name']);
        $isAgency = !$isAgent && !empty($property['agency_name']);
        $contactName = $isAgent
            ? $property['agent_first_name'] . ' ' . $property['agent_last_name']
            : ($isAgency ? $property['agency_name'] : $property['advertiser_first_name'] . ' ' . $property['advertiser_last_name']);
        $avatarUrl = $isAgent ? ($property['agent_avatar'] ?? null) : ($isAgency ? ($property['agency_logo'] ?? null) : ($property['advertiser_avatar'] ?? null));
        $areaAtuacao = $isAgency ? ($property['agency_service_area'] ?: trim(($property['agency_city'] ?? '') . ($property['agency_state'] ? ' — ' . $property['agency_state'] : ''))) : ($property['advertiser_service_area'] ?? null);

        $phone = $property['contact_phone'] ?: ($property['agent_phone'] ?: ($property['agency_phone'] ?: $property['advertiser_phone']));
        $email = $property['contact_email'] ?: ($property['agent_email'] ?: ($property['agency_email'] ?: $property['advertiser_email']));
        $whatsappRaw = $property['contact_whatsapp'] ?: ($property['agent_whatsapp'] ?: ($property['agency_whatsapp'] ?: ($property['advertiser_whatsapp'] ?: $phone)));
        $whatsapp = $whatsappRaw ? preg_replace('/\D/', '', $whatsappRaw) : null;
        $bio = $isAgent ? ($property['agent_bio'] ?? null) : ($isAgency ? ($property['agency_bio'] ?? null) : ($property['advertiser_bio'] ?? null));
        $memberSince = $isAgent ? ($property['agent_created_at'] ?? null) : ($isAgency ? ($property['agency_created_at'] ?? null) : ($property['advertiser_created_at'] ?? null));
        ?>
        <div class="flex items-center gap-3">
          <?php if ($avatarUrl): ?>
            <img src="<?= e($avatarUrl) ?>" alt="" class="h-14 w-14 shrink-0 rounded-full object-cover">
          <?php else: ?>
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-primary text-lg font-semibold text-white"><?= e(mb_strtoupper(mb_substr($contactName, 0, 1))) ?></span>
          <?php endif; ?>
          <div>
            <p class="text-xs font-semibold uppercase text-brand-text-secondary">Anunciado por</p>
            <p class="text-lg font-bold leading-tight"><?= e($contactName) ?></p>
            <?php if ($memberSince): ?><p class="text-xs text-brand-text-secondary">No Habitou Imóveis desde <?= e(date('Y', strtotime($memberSince))) ?></p><?php endif; ?>
          </div>
        </div>
        <?php if (!empty($property['agent_creci'])): ?>
          <p class="mt-2 flex items-center gap-1 text-xs text-brand-text-secondary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
            CRECI <?= e($property['agent_creci']) ?> verificado
          </p>
        <?php endif; ?>
        <?php if ($areaAtuacao): ?><p class="mt-1 text-xs text-brand-text-secondary">Atua em: <?= e($areaAtuacao) ?></p><?php endif; ?>
        <?php if ($bio): ?><p class="mt-3 border-t border-brand-border pt-3 text-sm leading-relaxed text-brand-text-secondary"><?= nl2br(e($bio)) ?></p><?php endif; ?>
        <?php if ($isAgent): ?>
          <a href="<?= base_url('corretor.php?id=' . (int) $property['agent_id']) ?>" class="mt-2 block text-sm font-semibold text-brand-primary hover:underline">Ver perfil completo</a>
        <?php endif; ?>
        <?php if ($isAgent && !empty($property['agency_name'])): ?>
          <a href="<?= base_url('imobiliaria.php?slug=' . $property['agency_slug']) ?>" class="mt-1 block text-sm text-brand-primary hover:underline"><?= e($property['agency_name']) ?></a>
        <?php elseif (!empty($property['agency_slug'])): ?>
          <a href="<?= base_url('imobiliaria.php?slug=' . $property['agency_slug']) ?>" class="mt-1 block text-sm text-brand-primary hover:underline">Ver página da imobiliária</a>
        <?php endif; ?>
        <div class="mt-4 flex flex-col gap-2">
          <?php if ($whatsapp): ?>
            <a href="https://wa.me/55<?= e($whatsapp) ?>?text=<?= urlencode('Olá! Tenho interesse no imóvel "' . $property['title'] . '" (código ' . $property['code'] . ').') ?>" target="_blank" rel="noopener noreferrer" class="rounded-full bg-brand-green px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-green-hover">Conversar no WhatsApp</a>
          <?php endif; ?>
          <?php if ($phone): ?>
            <a href="tel:<?= e(preg_replace('/\D/', '', $phone)) ?>" class="rounded-full border border-brand-border px-4 py-2.5 text-center text-sm font-semibold hover:border-brand-primary"><?= e($phone) ?></a>
          <?php endif; ?>
          <?php if ($email): ?>
            <a href="mailto:<?= e($email) ?>" class="rounded-full border border-brand-border px-4 py-2.5 text-center text-sm font-semibold hover:border-brand-primary">Enviar e-mail</a>
          <?php endif; ?>
          <a href="<?= base_url('fale-conosco.php?imovel=' . urlencode($property['code'])) ?>" class="rounded-full border border-brand-border px-4 py-2.5 text-center text-sm font-semibold hover:border-brand-primary">Enviar mensagem</a>
        </div>
        <p class="mt-4 text-xs text-brand-text-secondary">Código do imóvel: <?= e($property['code']) ?></p>
      </div>
    </div>
  </div>

  <div class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-brand-border bg-white px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.08)] lg:hidden">
    <div>
      <p class="text-base font-bold text-brand-text"><?= format_currency_brl($price) ?><?= $property['listing_type'] === 'RENT' ? '<span class="text-xs font-normal text-brand-text-secondary">/mês</span>' : '' ?></p>
      <a href="#anunciante" class="text-xs text-brand-text-secondary underline">Ver contato</a>
    </div>
    <?php if ($whatsapp): ?>
      <a href="https://wa.me/55<?= e($whatsapp) ?>?text=<?= urlencode('Olá! Tenho interesse no imóvel "' . $property['title'] . '" (código ' . $property['code'] . ').') ?>" target="_blank" rel="noopener noreferrer" class="shrink-0 rounded-full bg-brand-green px-6 py-3 text-sm font-semibold text-white hover:bg-brand-green-hover">Conversar no WhatsApp</a>
    <?php else: ?>
      <a href="#anunciante" class="shrink-0 rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Entrar em contato</a>
    <?php endif; ?>
  </div>

  <?php if ($similar): ?>
    <div class="mt-12">
      <h2 class="mb-4 text-xl font-bold">Imóveis semelhantes</h2>
      <?php render_property_grid($similar, $favoriteIds); ?>
    </div>
  <?php endif; ?>
</div>
<script src="<?= asset_url('assets/js/property-gallery.js') ?>"></script>
<script>
(function () {
  var text = document.getElementById('description-text');
  var toggle = document.getElementById('description-toggle');
  if (!text || !toggle) return;
  if (text.scrollHeight > text.clientHeight + 2) {
    toggle.classList.remove('hidden');
  }
  toggle.addEventListener('click', function () {
    var isClamped = text.classList.toggle('line-clamp-6');
    toggle.textContent = isClamped ? 'Mostrar mais' : 'Mostrar menos';
  });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
