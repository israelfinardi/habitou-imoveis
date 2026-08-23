<?php
function render_property_card(array $p, bool $isFavorite = false): void
{
    $price = $p['listing_type'] === 'RENT' ? ($p['price_rent'] ?? null) : ($p['price_sale'] ?? null);
    $href = property_href($p);
    // Título no padrão Airbnb: "Tipo · Cidade" (ex.: "Casa · Rio do Sul").
    $cardTitle = e(PROPERTY_TYPE_LABEL[$p['property_type']] ?? 'Imóvel') . ' · ' . e($p['city_name']);
    $meta = array_filter([
        !empty($p['bedrooms']) ? (int) $p['bedrooms'] . ' quarto' . ((int) $p['bedrooms'] > 1 ? 's' : '') : null,
        !empty($p['bathrooms']) ? (int) $p['bathrooms'] . ' banheiro' . ((int) $p['bathrooms'] > 1 ? 's' : '') : null,
        !empty($p['parking_spaces']) ? (int) $p['parking_spaces'] . ' vaga' . ((int) $p['parking_spaces'] > 1 ? 's' : '') : null,
    ]);
    ?>
    <?php $images = !empty($p['images']) ? $p['images'] : (!empty($p['image_url']) ? [$p['image_url']] : []); ?>
    <article data-property-id="<?= (int) $p['id'] ?>" class="group">
      <a href="<?= e($href) ?>" class="js-carousel relative block aspect-square w-full overflow-hidden rounded-2xl bg-brand-bg-subtle sm:aspect-[4/3]">
        <?php if ($images): ?>
          <div class="js-carousel-track scrollbar-none flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth">
            <?php foreach ($images as $img): ?>
              <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" class="h-full w-full shrink-0 snap-center object-cover">
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="flex h-full items-center justify-center text-sm text-brand-text-secondary">Sem foto</div>
        <?php endif; ?>
        <?php if (count($images) > 1): ?>
          <button type="button" class="js-carousel-prev absolute left-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Foto anterior">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <button type="button" class="js-carousel-next absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Próxima foto">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
          </button>
          <div class="js-carousel-dots pointer-events-none absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1">
            <?php foreach ($images as $i => $_): ?>
              <span class="h-1.5 w-1.5 rounded-full <?= $i === 0 ? 'bg-white' : 'bg-white/50' ?>"></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <span class="pointer-events-none absolute left-3 top-3 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-brand-text shadow">
          <?= $p['listing_type'] === 'RENT' ? 'Aluguel' : 'Venda' ?>
        </span>
        <button type="button" class="js-favorite-btn js-favorite-overlay <?= $isFavorite ? 'is-favorite' : '' ?> absolute right-3 top-3 flex h-8 w-8 items-center justify-center" data-property-id="<?= (int) $p['id'] ?>" aria-label="Favoritar">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="<?= $isFavorite ? '#C1502E' : 'rgba(0,0,0,.5)' ?>" stroke="#fff" stroke-width="1.5" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,.3))"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>
        </button>
      </a>
      <div class="pt-3">
        <a href="<?= e($href) ?>" class="block truncate text-[15px] font-semibold text-brand-text"><?= $cardTitle ?></a>
        <?php if (!empty($p['neighborhood_name'])): ?>
          <p class="mt-0.5 truncate text-sm text-brand-text-secondary"><?= e($p['neighborhood_name']) ?></p>
        <?php endif; ?>
        <?php if ($meta): ?>
          <p class="mt-0.5 truncate text-sm text-brand-text-secondary"><?= e(implode(' · ', $meta)) ?></p>
        <?php endif; ?>
        <p class="mt-1.5 text-[15px] font-semibold text-brand-text"><?= format_currency_brl($price) ?><?= $p['listing_type'] === 'RENT' ? '<span class="font-normal text-brand-text-secondary">/mês</span>' : '' ?></p>
      </div>
    </article>
    <?php
}

/**
 * Galeria da página do imóvel, estilo Airbnb: grade (1 foto grande + até 4
 * pequenas) no desktop, carrossel deslizável no mobile (reaproveitando o
 * mesmo .js-carousel dos cards de listagem), e um lightbox em tela cheia
 * (assets/js/property-gallery.js) aberto ao clicar em qualquer foto ou no
 * botão "Mostrar todas as fotos".
 */
function render_property_gallery(array $images, string $title): void
{
    $count = count($images);
    ?>
    <div class="js-lightbox-root" data-title="<?= e($title) ?>">
      <script type="application/json" class="js-lightbox-data"><?= json_encode(array_column($images, 'url'), JSON_UNESCAPED_SLASHES) ?></script>

      <?php if ($count === 0): ?>
        <div class="flex aspect-[16/9] w-full items-center justify-center rounded-xl bg-brand-bg-subtle text-brand-text-secondary lg:aspect-[21/9]">Sem fotos</div>
      <?php else: ?>
        <div class="js-carousel group relative block aspect-square w-full overflow-hidden rounded-xl bg-brand-bg-subtle sm:aspect-[4/3] lg:hidden">
          <div class="js-carousel-track scrollbar-none flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth">
            <?php foreach ($images as $i => $img): ?>
              <img src="<?= e($img['url']) ?>" data-index="<?= $i ?>" alt="<?= e($title) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" class="js-lightbox-trigger h-full w-full shrink-0 cursor-pointer snap-center object-cover">
            <?php endforeach; ?>
          </div>
          <?php if ($count > 1): ?>
            <button type="button" class="js-carousel-prev absolute left-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Foto anterior"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
            <button type="button" class="js-carousel-next absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text opacity-0 shadow transition group-hover:opacity-100" aria-label="Próxima foto"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
            <div class="js-carousel-dots pointer-events-none absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1">
              <?php foreach ($images as $i => $_): ?><span class="h-1.5 w-1.5 rounded-full <?= $i === 0 ? 'bg-white' : 'bg-white/50' ?>"></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <?php
        $mainImages = array_slice($images, 0, 5);
        $gridCols = $count === 1 ? 'lg:grid-cols-1' : ($count === 2 ? 'lg:grid-cols-2' : ($count === 3 ? 'lg:grid-cols-2 lg:grid-rows-2' : 'lg:grid-cols-4 lg:grid-rows-2'));
        ?>
        <div class="relative hidden overflow-hidden rounded-xl bg-brand-bg-subtle lg:grid lg:gap-1.5 <?= $gridCols ?>" style="aspect-ratio:<?= $count === 1 ? '16/8' : '16/7' ?>">
          <?php foreach ($mainImages as $i => $img):
              $span = ($count >= 4 && $i === 0) ? 'lg:col-span-2 lg:row-span-2' : (($count === 3 && $i === 0) ? 'lg:row-span-2' : '');
          ?>
            <div class="relative overflow-hidden <?= $span ?>">
              <img src="<?= e($img['url']) ?>" data-index="<?= $i ?>" alt="<?= e($title) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" class="js-lightbox-trigger h-full w-full cursor-pointer object-cover">
            </div>
          <?php endforeach; ?>
          <?php if ($count > 1): ?>
            <button type="button" class="js-lightbox-trigger absolute bottom-4 right-4 flex items-center gap-2 rounded-lg border border-brand-text bg-white px-4 py-2 text-xs font-semibold shadow hover:bg-brand-bg-subtle" data-index="0">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
              Mostrar todas as fotos
            </button>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div id="lightbox-modal" class="fixed inset-0 z-[500] hidden flex-col bg-black">
      <div class="flex shrink-0 items-center justify-between px-4 py-3">
        <button type="button" id="lightbox-close" class="flex h-9 w-9 items-center justify-center rounded-full text-white hover:bg-white/10" aria-label="Fechar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <span id="lightbox-counter" class="text-sm font-medium text-white"></span>
        <span class="w-9"></span>
      </div>
      <div class="js-carousel group relative min-h-0 flex-1">
        <div id="lightbox-track" class="js-carousel-track scrollbar-none flex h-full w-full snap-x snap-mandatory overflow-x-auto scroll-smooth"></div>
        <button type="button" class="js-carousel-prev absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text shadow hover:bg-white" aria-label="Foto anterior"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
        <button type="button" class="js-carousel-next absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-brand-text shadow hover:bg-white" aria-label="Próxima foto"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
      </div>
    </div>
    <?php
}

function render_property_grid(array $items, array $favoriteIds = [], string $emptyMessage = 'Nenhum imóvel encontrado com esses filtros.', ?string $gridId = null, bool $threeCols = true): void
{
    if (empty($items)) {
        echo '<div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">' . e($emptyMessage) . '</div>';
        return;
    }
    $idAttr = $gridId ? ' id="' . e($gridId) . '"' : '';
    $cols = $threeCols ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2';
    echo '<div' . $idAttr . ' class="grid grid-cols-1 gap-5 ' . $cols . '">';
    foreach ($items as $p) {
        render_property_card($p, in_array((int) $p['id'], $favoriteIds, true));
    }
    echo '</div>';
}

/**
 * Bloco de carrossel horizontal estilo Airbnb ("Vistos recentemente",
 * "Perto de você" etc. na home): título + link "Ver mais" à direita,
 * cartões de largura fixa rolando na horizontal com scroll-snap (sem grid
 * que quebra linha) e setas de navegação prev/next em telas grandes.
 * Não renderiza nada se a lista vier vazia — como no Airbnb, um bloco sem
 * resultado simplesmente não aparece, sem mensagem de "nada encontrado".
 */
function render_home_carousel(string $id, string $heading, array $items, array $favoriteIds = [], ?string $href = null, ?string $subtitle = null, ?Closure $cardRenderer = null, int $cardWidth = 240): void
{
    if (empty($items)) {
        return;
    }
    $cardRenderer ??= fn ($p) => render_property_card($p, in_array((int) $p['id'], $favoriteIds, true));
    ?>
    <section id="<?= e($id) ?>" class="js-home-carousel-section mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-4 flex items-end justify-between gap-3">
        <div>
          <h2 class="text-lg font-bold text-brand-text"><?= e($heading) ?></h2>
          <?php if ($subtitle): ?><p class="mt-0.5 text-xs text-brand-text-secondary"><?= e($subtitle) ?></p><?php endif; ?>
        </div>
        <?php if ($href): ?><a href="<?= e($href) ?>" class="shrink-0 text-sm font-medium text-brand-primary hover:underline">Ver mais →</a><?php endif; ?>
      </div>
      <div class="relative">
        <button type="button" class="js-home-carousel-prev absolute -left-3 top-[92px] z-10 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-brand-border bg-white shadow lg:flex" aria-label="Rolar para a esquerda">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
        </button>
        <div class="js-home-carousel-track flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2" style="scrollbar-width:none">
          <?php foreach ($items as $p): ?>
            <div class="w-[<?= (int) $cardWidth ?>px] shrink-0 snap-start"><?php $cardRenderer($p); ?></div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="js-home-carousel-next absolute -right-3 top-[92px] z-10 hidden h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full border border-brand-border bg-white shadow lg:flex" aria-label="Rolar para a direita">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
      </div>
    </section>
    <?php
}

/**
 * Coluna de listagem dos resultados (usada junto de render_results_map()
 * num layout de 3 colunas: filtros 15% / lista 40% / mapa 45%). O breakpoint
 * de 3 colunas é mais alto que o do grid de largura cheia (xl em vez de lg)
 * porque essa coluna ocupa só uma fatia da página, ao lado do mapa.
 */
function render_property_list(array $items, array $favoriteIds = [], string $emptyMessage = 'Nenhum imóvel encontrado com esses filtros.'): void
{
    if (empty($items)) {
        echo '<div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">' . e($emptyMessage) . '</div>';
        return;
    }
    echo '<div id="results-grid" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">';
    foreach ($items as $p) {
        render_property_card($p, in_array((int) $p['id'], $favoriteIds, true));
    }
    echo '</div>';
}

/**
 * Mapa interativo (estilo Airbnb) com os pins de preço dos resultados,
 * sincronizado por hover com os cards da lista. Sticky no desktop e em
 * tela cheia (via botão "Ver no mapa") no celular.
 */
function render_results_map(array $items, array $favoriteIds = []): void
{
    $pins = [];
    foreach ($items as $p) {
        $lat = $p['latitude'] ?: ($p['city_lat'] ?? null);
        $lng = $p['longitude'] ?: ($p['city_lng'] ?? null);
        if (!$lat || !$lng) {
            continue;
        }
        $price = $p['listing_type'] === 'RENT' ? ($p['price_rent'] ?? null) : ($p['price_sale'] ?? null);
        $meta = array_filter([
            !empty($p['bedrooms']) ? (int) $p['bedrooms'] . ' quarto' . ((int) $p['bedrooms'] > 1 ? 's' : '') : null,
            !empty($p['bathrooms']) ? (int) $p['bathrooms'] . ' banheiro' . ((int) $p['bathrooms'] > 1 ? 's' : '') : null,
            !empty($p['parking_spaces']) ? (int) $p['parking_spaces'] . ' vaga' . ((int) $p['parking_spaces'] > 1 ? 's' : '') : null,
        ]);
        $pins[] = [
            'id' => (int) $p['id'],
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => format_price_short($price, $p['listing_type'] === 'RENT'),
            'price' => format_currency_brl($price) . ($p['listing_type'] === 'RENT' ? '/mês' : ''),
            'title' => (PROPERTY_TYPE_LABEL[$p['property_type']] ?? 'Imóvel') . ' · ' . $p['city_name'],
            'neighborhood' => $p['neighborhood_name'] ?? '',
            'meta' => implode(' · ', $meta),
            'images' => !empty($p['images']) ? $p['images'] : (!empty($p['image_url']) ? [$p['image_url']] : []),
            'isFavorite' => in_array((int) $p['id'], $favoriteIds, true),
            'href' => property_href($p),
        ];
    }
    // O mapa é sempre renderizado (mesmo sem pins), com uma vista padrão do
    // Brasil inteiro — assim a coluna do mapa nunca fica em branco. No
    // celular ele fica fixo em tela cheia por trás da lista, que vira uma
    // bandeja arrastável por cima (ver assets/js/results-map.js).
    ?>
    <div id="results-map-wrap" class="h-[60vh] overflow-hidden rounded-xl border border-brand-border lg:sticky lg:top-24 lg:h-[calc(100vh-7rem)]">
      <div id="results-map" class="w-full" style="height:100%"></div>
    </div>
    <script>window.__RESULTS_MAP_PINS = <?= json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= asset_url('assets/js/results-map.js') ?>"></script>
    <?php
}

function render_pagination(int $page, int $totalPages, string $baseQuery): void
{
    if ($totalPages <= 1) {
        return;
    }
    echo '<nav class="mt-8 flex flex-wrap items-center justify-center gap-1">';
    for ($p = 1; $p <= $totalPages; $p++) {
        if ($p !== 1 && $p !== $totalPages && abs($p - $page) > 2) {
            if ($p === 2 || $p === $totalPages - 1) {
                echo '<span class="px-1 text-brand-text-secondary">…</span>';
            }
            continue;
        }
        $sep = strpos($baseQuery, '?') !== false ? '&' : '?';
        $href = $baseQuery . $sep . 'pagina=' . $p;
        $active = $p === $page;
        printf(
            '<a href="%s" class="rounded-md border px-3 py-1.5 text-sm %s">%d</a>',
            e($href),
            $active ? 'border-brand-primary bg-brand-primary text-white' : 'border-brand-border hover:border-brand-primary',
            $p
        );
    }
    echo '</nav>';
}
