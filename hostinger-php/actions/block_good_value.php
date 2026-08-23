<?php
/**
 * Bloco "Oportunidades e bom valor" — imóveis com preço abaixo da média do
 * próprio bairro (ou da cidade, na falta de amostra suficiente por bairro).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$defaultListingType = SLUG_TO_LISTING_TYPE[$_GET['transacao'] ?? ''] ?? 'SALE';

$cityId = resolve_reference_city_id($user, session_id(), client_ip());
$items = get_good_value_properties($cityId, $defaultListingType);

render_home_carousel('home-good-value', 'Oportunidades de bom valor', $items, $favoriteIds, null, 'Preço abaixo da média do bairro');
