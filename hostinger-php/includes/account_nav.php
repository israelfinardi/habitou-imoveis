<?php
function render_account_nav(string $active): void
{
    $items = [
        'overview' => ['minha-conta.php', 'Visão geral'],
        'dados' => ['minha-conta-dados.php', 'Meus dados'],
        'senha' => ['minha-conta-senha.php', 'Alterar senha'],
        'anuncios' => ['anunciante/imoveis.php', 'Meus anúncios'],
        'favoritos' => ['minha-conta-favoritos.php', 'Favoritos'],
    ];
    echo '<nav class="flex flex-col gap-1">';
    foreach ($items as $key => [$href, $label]) {
        $cls = $key === $active ? 'text-brand-primary font-semibold' : 'text-brand-text';
        echo '<a href="' . e(base_url($href)) . '" class="rounded-lg px-3 py-2 text-sm ' . $cls . ' hover:bg-brand-bg-subtle">' . e($label) . '</a>';
    }
    echo '</nav>';
}
