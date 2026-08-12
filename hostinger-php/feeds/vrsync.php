<?php
/**
 * Exporta o catálogo (imóveis publicados) no mesmo formato XML aceito pelo
 * importador VRSync, permitindo que outro sistema consuma o catálogo.
 * Parâmetro opcional: ?imobiliaria=<slug>
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$agencySlug = $_GET['imobiliaria'] ?? null;
$where = 'p.status = "PUBLISHED"';
$args = [];
if ($agencySlug) {
    $where .= ' AND ag.slug = ?';
    $args[] = $agencySlug;
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS city_name, c.state_code, n.name AS neighborhood_name
    FROM properties p
    JOIN cities c ON c.id = p.city_id
    LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
    LEFT JOIN agencies ag ON ag.id = p.agency_id
    WHERE $where
    ORDER BY p.published_at DESC
    LIMIT 5000
");
$stmt->execute($args);
$properties = $stmt->fetchAll();

function xml_escape(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo "<Carga>\n";
echo '  <DataGeracao>' . date('c') . "</DataGeracao>\n";
echo "  <Imoveis>\n";

$imgStmt = $pdo->prepare('SELECT url FROM property_images WHERE property_id = ? ORDER BY `order`');

foreach ($properties as $p) {
    $imgStmt->execute([$p['id']]);
    $photos = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    echo "    <Imovel>\n";
    echo '      <CodigoImovel>' . xml_escape($p['external_code'] ?: $p['code']) . "</CodigoImovel>\n";
    echo '      <CodigoReferencia>' . xml_escape($p['code']) . "</CodigoReferencia>\n";
    echo '      <Titulo>' . xml_escape($p['title']) . "</Titulo>\n";
    echo '      <Descricao>' . xml_escape($p['description']) . "</Descricao>\n";
    echo '      <TipoImovel>' . xml_escape(PROPERTY_TYPE_LABEL[$p['property_type']]) . "</TipoImovel>\n";
    echo '      <Finalidade>' . xml_escape(LISTING_TYPE_LABEL[$p['listing_type']]) . "</Finalidade>\n";
    echo "      <Status>Ativo</Status>\n";
    echo "      <Precos>\n";
    echo '        <PrecoVenda>' . xml_escape((string) $p['price_sale']) . "</PrecoVenda>\n";
    echo '        <PrecoLocacao>' . xml_escape((string) $p['price_rent']) . "</PrecoLocacao>\n";
    echo '        <PrecoCondominio>' . xml_escape((string) $p['condo_fee']) . "</PrecoCondominio>\n";
    echo '        <PrecoIptu>' . xml_escape((string) $p['iptu']) . "</PrecoIptu>\n";
    echo "      </Precos>\n";
    echo "      <Caracteristicas>\n";
    echo '        <AreaTotal>' . xml_escape((string) $p['total_area']) . "</AreaTotal>\n";
    echo '        <AreaConstruida>' . xml_escape((string) $p['built_area']) . "</AreaConstruida>\n";
    echo '        <Quartos>' . xml_escape((string) $p['bedrooms']) . "</Quartos>\n";
    echo '        <Suites>' . xml_escape((string) $p['suites']) . "</Suites>\n";
    echo '        <Banheiros>' . xml_escape((string) $p['bathrooms']) . "</Banheiros>\n";
    echo '        <Vagas>' . xml_escape((string) $p['parking_spaces']) . "</Vagas>\n";
    foreach (json_decode_safe($p['features']) as $f) {
        echo '        <Comodidade>' . xml_escape($f) . "</Comodidade>\n";
    }
    echo "      </Caracteristicas>\n";
    echo "      <Endereco>\n";
    echo '        <Logradouro>' . xml_escape($p['street']) . "</Logradouro>\n";
    echo '        <Numero>' . xml_escape($p['number']) . "</Numero>\n";
    echo '        <Bairro>' . xml_escape($p['neighborhood_name']) . "</Bairro>\n";
    echo '        <Cidade>' . xml_escape($p['city_name']) . "</Cidade>\n";
    echo '        <Estado>' . xml_escape($p['state_code']) . "</Estado>\n";
    echo '        <CEP>' . xml_escape($p['zip_code']) . "</CEP>\n";
    echo '        <Latitude>' . xml_escape((string) $p['latitude']) . "</Latitude>\n";
    echo '        <Longitude>' . xml_escape((string) $p['longitude']) . "</Longitude>\n";
    echo "      </Endereco>\n";
    echo "      <Fotos>\n";
    foreach ($photos as $url) {
        echo '        <Foto>' . xml_escape($url) . "</Foto>\n";
    }
    echo "      </Fotos>\n";
    echo "    </Imovel>\n";
}

echo "  </Imoveis>\n";
echo "</Carga>\n";
