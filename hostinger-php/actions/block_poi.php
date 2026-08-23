<?php
/**
 * Bloco "Perto de pontos de interesse" — universidades, polos empresariais
 * etc. cadastrados pelo admin (admin/pontos-de-interesse.php), na cidade de
 * referência do visitante.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];
$defaultListingType = SLUG_TO_LISTING_TYPE[$_GET['transacao'] ?? ''] ?? 'SALE';

$cityId = resolve_reference_city_id($user, session_id(), client_ip());
$result = get_nearby_poi_properties($cityId, $defaultListingType);

if ($result) {
    render_home_carousel('home-poi', 'Perto de ' . $result['poi']['name'], $result['items'], $favoriteIds);
}
