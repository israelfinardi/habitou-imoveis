<?php
/**
 * Recarrega a seção de recomendações da home depois que o navegador cede o
 * GPS (ver assets/js/home-recommendations.js): actions/set_location.php já
 * gravou a localização precisa da sessão, então basta rodar de novo o mesmo
 * algoritmo de index.php e devolver só o HTML da grade de imóveis, pronta
 * pra substituir o conteúdo que tinha sido renderizado com geolocalização
 * por IP (ou o fallback) no primeiro carregamento da página.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/recommendation_service.php';
header('Content-Type: text/html; charset=UTF-8');

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$defaultListingType = SLUG_TO_LISTING_TYPE[$_GET['transacao'] ?? ''] ?? 'SALE';

$recommendations = get_home_recommendations($user, session_id(), client_ip(), $defaultListingType, HOME_RECOMMENDATIONS_LIMIT);
render_property_grid($recommendations['items'], $favoriteIds, 'Nenhum imóvel encontrado com esses filtros.', 'home-recommendations-grid');
