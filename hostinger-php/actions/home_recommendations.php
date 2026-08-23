<?php
/**
 * Bloco "Recomendado para você" / "Perto de você" da home — carregado de
 * forma assíncrona (ver assets/js/home-blocks.js) pra não travar o
 * carregamento inicial da página com a consulta de geolocalização.
 * Também é chamado de novo depois que o navegador cede o GPS
 * (actions/set_location.php já gravou a localização precisa da sessão),
 * pra fazer o upgrade de IP aproximado pra GPS preciso sem reload.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$defaultListingType = SLUG_TO_LISTING_TYPE[$_GET['transacao'] ?? ''] ?? 'SALE';

$recommendations = get_home_recommendations($user, session_id(), client_ip(), $defaultListingType, HOME_RECOMMENDATIONS_LIMIT);
$label = match ($recommendations['mode']) {
    'personalized' => 'Recomendado para você',
    'geo' => 'Perto de você',
    default => 'Imóveis em destaque',
};
render_home_carousel('home-recommendations', $label, $recommendations['items'], $favoriteIds, base_url('imoveis.php'));
