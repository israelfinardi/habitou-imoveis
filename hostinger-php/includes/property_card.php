<?php
function render_property_card(array $p, bool $isFavorite = false): void
{
    $price = $p['listing_type'] === 'RENT' ? ($p['price_rent'] ?? null) : ($p['price_sale'] ?? null);
    $href = property_href($p);
    $meta = array_filter([
        !empty($p['bedrooms']) ? (int) $p['bedrooms'] . ' dorm' : null,
        !empty($p['parking_spaces']) ? (int) $p['parking_spaces'] . ' vaga(s)' : null,
        !empty($p['total_area']) ? format_area($p['total_area']) : null,
    ]);
    ?>
    <article data-property-id="<?= (int) $p['id'] ?>" class="group">
      <a href="<?= e($href) ?>" class="relative block aspect-square w-full overflow-hidden rounded-xl bg-brand-bg-subtle sm:aspect-[4/3]">
        <?php if (!empty($p['image_url'])): ?>
          <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        <?php else: ?>
          <div class="flex h-full items-center justify-center text-sm text-brand-text-secondary">Sem foto</div>
        <?php endif; ?>
        <span class="absolute left-3 top-3 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-brand-text shadow">
          <?= $p['listing_type'] === 'RENT' ? 'Aluguel' : 'Venda' ?>
        </span>
        <button type="button" class="js-favorite-btn js-favorite-overlay <?= $isFavorite ? 'is-favorite' : '' ?> absolute right-3 top-3 flex h-8 w-8 items-center justify-center" data-property-id="<?= (int) $p['id'] ?>" aria-label="Favoritar">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="<?= $isFavorite ? '#C1502E' : 'rgba(0,0,0,.5)' ?>" stroke="#fff" stroke-width="1.5" style="filter:drop-shadow(0 1px 2px rgba(0,0,0,.3))"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>
        </button>
      </a>
      <div class="pt-3">
        <a href="<?= e($href) ?>" class="block truncate text-[15px] font-semibold text-brand-text"><?= e($p['title']) ?></a>
        <p class="mt-0.5 truncate text-sm text-brand-text-secondary">
          <?= !empty($p['neighborhood_name']) ? e($p['neighborhood_name']) . ', ' : '' ?><?= e($p['city_name']) ?> — <?= e($p['state_code']) ?>
        </p>
        <?php if ($meta): ?>
          <p class="mt-0.5 truncate text-sm text-brand-text-secondary"><?= e(implode(' · ', $meta)) ?> · <?= e(PROPERTY_TYPE_LABEL[$p['property_type']]) ?></p>
        <?php endif; ?>
        <p class="mt-1.5 text-[15px] font-semibold text-brand-text"><?= format_currency_brl($price) ?><?= $p['listing_type'] === 'RENT' ? '<span class="font-normal text-brand-text-secondary">/mês</span>' : '' ?></p>
      </div>
    </article>
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
 * Coluna de listagem dos resultados (usada junto de render_results_map()
 * num layout de 3 colunas: filtros 15% / lista 40% / mapa 45%).
 */
function render_property_list(array $items, array $favoriteIds = [], string $emptyMessage = 'Nenhum imóvel encontrado com esses filtros.'): void
{
    render_property_grid($items, $favoriteIds, $emptyMessage, 'results-grid', false);
}

/**
 * Mapa interativo (estilo Airbnb) com os pins de preço dos resultados,
 * sincronizado por hover com os cards da lista. Sticky no desktop e em
 * tela cheia (via botão "Ver no mapa") no celular.
 */
function render_results_map(array $items): void
{
    $pins = [];
    foreach ($items as $p) {
        $lat = $p['latitude'] ?: ($p['city_lat'] ?? null);
        $lng = $p['longitude'] ?: ($p['city_lng'] ?? null);
        if (!$lat || !$lng) {
            continue;
        }
        $price = $p['listing_type'] === 'RENT' ? ($p['price_rent'] ?? null) : ($p['price_sale'] ?? null);
        $meta = trim((!empty($p['neighborhood_name']) ? $p['neighborhood_name'] . ', ' : '') . $p['city_name']);
        $pins[] = [
            'id' => (int) $p['id'],
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => format_price_short($price, $p['listing_type'] === 'RENT'),
            'title' => $p['title'],
            'meta' => $meta,
            'image' => $p['image_url'] ?? null,
            'href' => property_href($p),
        ];
    }
    if (!$pins) {
        return;
    }
    ?>
    <button type="button" id="map-toggle-btn" class="mb-3 flex items-center gap-2 rounded-full border border-brand-border px-4 py-2 text-sm font-semibold hover:border-brand-primary lg:hidden">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 20l-6-3V4l6 3 6-3 6 3v13l-6-3-6 3z"/><path d="M9 4v13M15 7v13"/></svg>
      Ver no mapa
    </button>
    <div id="results-map-wrap" class="hidden overflow-hidden rounded-xl border border-brand-border lg:sticky lg:top-24 lg:block" style="height:calc(100vh - 7rem)">
      <button type="button" id="map-close-btn" class="absolute right-3 top-3 z-[1000] hidden h-9 w-9 items-center justify-center rounded-full bg-white shadow lg:hidden" aria-label="Fechar mapa">&times;</button>
      <div id="results-map" class="w-full" style="height:100%"></div>
    </div>
    <script>window.__RESULTS_MAP_PINS = <?= json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?= base_url('assets/js/results-map.js') ?>"></script>
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
