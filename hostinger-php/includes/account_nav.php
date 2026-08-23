<?php
require_once __DIR__ . '/admin_nav.php';

/**
 * Fonte única do leque de links da área logada — usada tanto pela sidebar
 * (render_account_nav, minha-conta.php/anunciante/imobiliaria/contratos)
 * quanto pelo dropdown "Minha conta" do header (includes/header.php). Antes
 * cada página montava sua própria lista à mão e elas divergiam entre si
 * (ex.: contratos.php/imobiliaria/*.php tinham uma sidebar totalmente
 * separada sem Favoritos/Planos/Meus dados, e o dropdown do header não
 * tinha Contratos nem Planos). Calculando os itens uma vez aqui, a partir
 * do usuário logado, os dois lugares ficam sempre iguais.
 */
function account_nav_items(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    $items = [
        'overview' => ['minha-conta.php', 'Visão geral', 'grid'],
        'anuncios' => ['anunciante/imoveis.php', 'Meus anúncios', 'house'],
        'favoritos' => ['minha-conta-favoritos.php', 'Favoritos', 'heart'],
        'contratos' => ['contratos.php', 'Contratos', 'document'],
        'contratos_modelos' => ['contratos-modelos.php', 'Modelos de contrato', 'pencil'],
    ];
    $items['planos'] = ['planos.php', 'Planos', 'tag'];
    $items['dados'] = ['minha-conta-dados.php', 'Perfil', 'user'];
    $items['senha'] = ['minha-conta-senha.php', 'Alterar senha', 'lock'];
    if ($user['role'] === 'AGENCY_ADMIN' && $user['agency_id']) {
        $items['imobiliaria'] = ['imobiliaria/perfil.php', 'Perfil da imobiliária', 'building'];
    }

    return $items;
}

function render_nav_icon(string $icon): string
{
    return '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">' . NAV_ICONS[$icon] . '</svg>';
}

/**
 * Menu lateral unificado — os mesmos links, na mesma ordem, com os mesmos
 * ícones, em toda página logada (conta, anunciante, imobiliária ou admin).
 * Usuários ADMIN veem, além dos itens pessoais, a seção "Administração"
 * completa aqui também — antes só o dropdown/menu mobile do header
 * mostrava isso, e as páginas admin/*.php tinham uma sidebar totalmente à
 * parte (sem ícone, sem os itens pessoais), o que fazia cada página exibir
 * um menu diferente.
 */
function render_account_nav(string $active): void
{
    $user = current_user();
    echo '<nav class="flex flex-col gap-1">';
    foreach (account_nav_items() as $key => [$href, $label, $icon]) {
        $cls = $key === $active ? 'text-brand-primary font-semibold' : 'text-brand-text';
        echo '<a href="' . e(base_url($href)) . '" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm ' . $cls . ' hover:bg-brand-bg-subtle">' . render_nav_icon($icon) . e($label) . '</a>';
    }
    if ($user && $user['role'] === 'ADMIN') {
        echo '<p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase text-brand-text-secondary">Administração</p>';
        foreach (admin_nav_items() as $key => [$href, $label, $icon]) {
            $cls = $key === $active ? 'text-brand-primary font-semibold' : 'text-brand-text';
            echo '<a href="' . e(base_url($href)) . '" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm ' . $cls . ' hover:bg-brand-bg-subtle">' . render_nav_icon($icon) . e($label) . '</a>';
        }
    }
    echo '<div class="my-2 border-t border-brand-border"></div>';
    echo '<a href="' . e(base_url('logout.php')) . '" class="rounded-lg px-3 py-2 text-sm text-brand-text hover:bg-brand-bg-subtle">Sair</a>';
    echo '</nav>';
}
