<?php
/**
 * Itens do menu de administração — mesclados por render_account_nav()
 * (includes/account_nav.php) dentro do MESMO menu lateral unificado sempre
 * que o usuário logado é ADMIN, assim a sidebar mostra as mesmas opções
 * (pessoais + administração) no mesmo lugar em qualquer página do site,
 * seja de conta, de anunciante ou de administração.
 */
function admin_nav_items(): array
{
    return [
        'admin_index' => ['admin/index.php', 'Visão geral', 'grid'],
        'admin_usuarios' => ['admin/usuarios.php', 'Usuários', 'users'],
        'admin_imoveis' => ['admin/imoveis.php', 'Imóveis', 'house'],
        'admin_imobiliarias' => ['admin/imobiliarias.php', 'Imobiliárias', 'building'],
        'admin_planos' => ['admin/planos.php', 'Planos', 'tag'],
        'admin_assinaturas' => ['admin/assinaturas.php', 'Assinaturas', 'card'],
        'admin_importacoes_xml' => ['admin/importacoes-xml.php', 'Importações XML', 'sync'],
        'admin_pontos_interesse' => ['admin/pontos-de-interesse.php', 'Pontos de interesse', 'pin'],
    ];
}

/** Compatibilidade: as páginas admin/*.php chamam render_admin_nav('imoveis'), etc. — traduz pra chave prefixada do menu unificado. */
function render_admin_nav(string $active): void
{
    render_account_nav('admin_' . $active);
}
