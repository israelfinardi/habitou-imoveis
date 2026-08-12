<?php
const CATEGORY_ICONS = [
    'APARTMENT' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4h6v4"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"/>',
    'HOUSE' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
    'LAND' => '<path d="M3 7l6-3 6 3 6-3v13l-6 3-6-3-6 3V7z"/><path d="M9 4v13M15 7v13"/>',
    'COMMERCIAL_ROOM' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>',
    'STORE' => '<path d="M3 9l1-5h16l1 5"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/><path d="M3 9a2 2 0 0 0 4 0M7 9a2 2 0 0 0 4 0M11 9a2 2 0 0 0 4 0M15 9a2 2 0 0 0 4 0"/>',
    'WAREHOUSE' => '<path d="M3 21V10l9-6 9 6v11"/><path d="M3 21h18"/><path d="M9 21v-7h6v7"/>',
    'RURAL' => '<path d="M12 3l5 7h-3l4 6h-4v5h-4v-5H6l4-6H7l5-7z"/>',
    'BUILDING' => '<rect x="6" y="2" width="12" height="20" rx="1"/><path d="M9 6h0M13 6h0M9 10h0M13 10h0M9 14h0M13 14h0M9 18h0M13 18h0"/>',
    'OTHER' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
];

/**
 * Fileira de categorias por tipo de imóvel (estilo Airbnb), abaixo do
 * cabeçalho. $activeType usa a mesma chave de PROPERTY_TYPE_LABEL (ex.: 'HOUSE').
 */
function render_category_pills(?string $activeType = null): void
{
    ?>
    <div class="border-b border-brand-border bg-white">
      <div class="scrollbar-none mx-auto flex max-w-7xl gap-7 overflow-x-auto px-4 py-3 sm:px-6 lg:px-8">
        <?php foreach (PROPERTY_TYPE_LABEL as $type => $label): $isActive = $activeType === $type; ?>
          <a href="<?= base_url('imoveis.php?tipo=' . PROPERTY_TYPE_SLUG[$type]) ?>"
             class="flex shrink-0 flex-col items-center gap-2 border-b-2 pb-2.5 pt-1 text-xs font-medium transition <?= $isActive ? 'border-brand-text text-brand-text' : 'border-transparent text-brand-text-secondary hover:border-brand-border hover:text-brand-text' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><?= CATEGORY_ICONS[$type] ?></svg>
            <span class="whitespace-nowrap"><?= e($label) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
