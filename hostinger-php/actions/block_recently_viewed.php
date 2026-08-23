<?php
/**
 * Bloco "Vistos recentemente" — os IDs vêm do localStorage do navegador
 * (ver assets/js/home-blocks.js), não de nenhuma tabela server-side.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$user = current_user();
$favoriteIds = $user ? get_favorite_ids($user['id']) : [];

$items = get_recently_viewed_properties($ids);
render_home_carousel('home-recently-viewed', 'Vistos recentemente', $items, $favoriteIds);
