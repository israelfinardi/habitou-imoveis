<?php
/**
 * Bloco "Explore cidades vizinhas" — cross-selling quando a busca do
 * visitante é muito específica de uma cidade: sugere municípios da mesma
 * região metropolitana com imóveis publicados, pra não perder o tráfego.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

$user = current_user();
$cityId = resolve_reference_city_id($user, session_id(), client_ip());
if (!$cityId) {
    exit;
}

$refCityName = db()->prepare('SELECT name FROM cities WHERE id = ?');
$refCityName->execute([$cityId]);
$refCityName = $refCityName->fetchColumn();

$cities = get_nearby_cities($cityId);

$cityCard = function (array $city): void {
    ?>
    <a href="<?= base_url('cidade.php?slug=' . e($city['slug'])) ?>" class="block h-full rounded-2xl border border-brand-border bg-white p-4 transition hover:-translate-y-0.5 hover:border-brand-primary hover:shadow">
      <p class="font-semibold text-brand-text"><?= e($city['name']) ?></p>
      <p class="mt-0.5 text-xs text-brand-text-secondary"><?= e($city['state_code']) ?> · <?= (int) round($city['distance_km']) ?> km</p>
      <p class="mt-3 text-sm font-semibold text-brand-primary"><?= (int) $city['property_count'] ?> imóve<?= (int) $city['property_count'] === 1 ? 'l' : 'is' ?></p>
    </a>
    <?php
};

render_home_carousel('home-nearby-cities', 'Explore cidades vizinhas', $cities, [], null, $refCityName ? "Perto de $refCityName" : null, $cityCard, 180);
