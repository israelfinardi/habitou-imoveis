<?php
function render_property_card(array $p, bool $isFavorite = false): void
{
    $price = $p['listing_type'] === 'RENT' ? ($p['price_rent'] ?? null) : ($p['price_sale'] ?? null);
    $href = property_href($p);
    ?>
    <article class="group overflow-hidden rounded-xl border border-brand-border bg-white transition hover:shadow-md">
      <a href="<?= e($href) ?>" class="relative block aspect-[4/3] w-full overflow-hidden bg-brand-bg-subtle">
        <?php if (!empty($p['image_url'])): ?>
          <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['title']) ?>" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        <?php else: ?>
          <div class="flex h-full items-center justify-center text-sm text-brand-text-secondary">Sem foto</div>
        <?php endif; ?>
        <span class="absolute left-3 top-3 rounded-full bg-brand-primary px-2.5 py-1 text-xs font-semibold text-white">
          <?= $p['listing_type'] === 'RENT' ? 'Aluguel' : 'Venda' ?>
        </span>
        <button type="button" class="js-favorite-btn <?= $isFavorite ? 'is-favorite' : '' ?> absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow" data-property-id="<?= (int) $p['id'] ?>" aria-label="Favoritar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $isFavorite ? '#c1502e' : 'none' ?>" stroke="<?= $isFavorite ? '#c1502e' : '#585b62' ?>" stroke-width="2"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>
        </button>
      </a>
      <div class="p-4">
        <p class="text-lg font-bold text-brand-text"><?= format_currency_brl($price) ?></p>
        <a href="<?= e($href) ?>" class="mt-1 block truncate text-sm font-medium hover:text-brand-primary"><?= e($p['title']) ?></a>
        <p class="mt-0.5 truncate text-xs text-brand-text-secondary">
          <?= !empty($p['neighborhood_name']) ? e($p['neighborhood_name']) . ', ' : '' ?><?= e($p['city_name']) ?> — <?= e($p['state_code']) ?>
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-brand-text-secondary">
          <?php if (!empty($p['bedrooms'])): ?><span><?= (int) $p['bedrooms'] ?> dorm</span><?php endif; ?>
          <?php if (!empty($p['parking_spaces'])): ?><span><?= (int) $p['parking_spaces'] ?> vaga(s)</span><?php endif; ?>
          <?php if (!empty($p['total_area'])): ?><span><?= format_area($p['total_area']) ?></span><?php endif; ?>
          <span class="ml-auto rounded bg-brand-bg-subtle px-1.5 py-0.5 text-[11px] font-medium"><?= e(PROPERTY_TYPE_LABEL[$p['property_type']]) ?></span>
        </div>
        <?php if (!empty($p['agency_name'])): ?>
          <p class="mt-2 truncate text-[11px] text-brand-text-secondary"><?= e($p['agency_name']) ?></p>
        <?php endif; ?>
      </div>
    </article>
    <?php
}

function render_property_grid(array $items, array $favoriteIds = [], string $emptyMessage = 'Nenhum imóvel encontrado com esses filtros.'): void
{
    if (empty($items)) {
        echo '<div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">' . e($emptyMessage) . '</div>';
        return;
    }
    echo '<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">';
    foreach ($items as $p) {
        render_property_card($p, in_array((int) $p['id'], $favoriteIds, true));
    }
    echo '</div>';
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
