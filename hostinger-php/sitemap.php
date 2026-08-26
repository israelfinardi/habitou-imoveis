<?php
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');

$appUrl = rtrim(APP_URL, '/');
$staticPages = ['', 'imoveis.php', 'imobiliarias.php', 'como-anunciar.php', 'quem-somos.php', 'fale-conosco.php', 'planos.php', 'guias.php', 'comparar.php', 'termos-de-uso.php', 'politica-de-privacidade.php', 'termos-assinatura.php', 'login.php', 'cadastro.php'];

$pdo = db();
$cities = $pdo->query('SELECT slug FROM cities')->fetchAll(PDO::FETCH_COLUMN);
$properties = $pdo->query('SELECT slug, listing_type, property_type, updated_at, (SELECT slug FROM cities c WHERE c.id = properties.city_id) AS city_slug FROM properties WHERE status = "PUBLISHED" LIMIT 5000')->fetchAll();
$agencies = $pdo->query('SELECT slug FROM agencies WHERE status = "ACTIVE"')->fetchAll(PDO::FETCH_COLUMN);
$guides = $pdo->query('SELECT slug FROM articles WHERE kind = "GUIDE"')->fetchAll(PDO::FETCH_COLUMN);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

function sitemap_url(string $loc, string $priority = '0.5'): void
{
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc><priority>' . $priority . "</priority></url>\n";
}

foreach ($staticPages as $p) {
    sitemap_url("$appUrl/$p", $p === '' ? '1.0' : '0.6');
}
foreach ($cities as $slug) {
    sitemap_url("$appUrl/cidade.php?slug=$slug", '0.8');
    sitemap_url("$appUrl/cidade.php?slug=$slug&transacao=comprar", '0.7');
    sitemap_url("$appUrl/cidade.php?slug=$slug&transacao=alugar", '0.7');
}
foreach ($properties as $p) {
    sitemap_url("$appUrl/imovel.php?slug=" . urlencode($p['slug']), '0.9');
}
foreach ($agencies as $slug) {
    sitemap_url("$appUrl/imobiliaria.php?slug=$slug", '0.5');
}
foreach ($guides as $slug) {
    sitemap_url("$appUrl/guia.php?slug=$slug", '0.3');
}

echo '</urlset>';
