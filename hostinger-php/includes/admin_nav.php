<?php
function render_admin_nav(string $active): void
{
    $items = [
        'index' => ['admin/index.php', 'Visão geral'],
        'usuarios' => ['admin/usuarios.php', 'Usuários'],
        'imoveis' => ['admin/imoveis.php', 'Imóveis'],
        'imobiliarias' => ['admin/imobiliarias.php', 'Imobiliárias'],
        'planos' => ['admin/planos.php', 'Planos'],
        'assinaturas' => ['admin/assinaturas.php', 'Assinaturas'],
        'feeds' => ['admin/feeds.php', 'Feeds VRSync'],
        'contratos' => ['admin/contratos.php', 'Contratos'],
    ];
    echo '<p class="mb-3 text-xs font-semibold uppercase text-brand-text-secondary">Administração</p><nav class="flex flex-col gap-1">';
    foreach ($items as $key => [$href, $label]) {
        $cls = $key === $active ? 'text-brand-primary font-semibold' : '';
        echo '<a href="' . e(base_url($href)) . '" class="rounded-lg px-3 py-2 text-sm ' . $cls . ' hover:bg-brand-bg-subtle">' . e($label) . '</a>';
    }
    echo '</nav>';
}
