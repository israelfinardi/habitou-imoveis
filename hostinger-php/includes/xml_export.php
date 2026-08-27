<?php
/**
 * Gera um XML no mesmo padrão VRSync aceito por includes/xml_import_parser.php
 * (estrutura Carga/Imoveis/Imovel) a partir dos imóveis do próprio Anunciante —
 * serve como backup da carteira ou pra alimentar outro sistema/portal
 * compatível com o padrão. Espelha exatamente as tags que o parser de
 * importação já lê, então um arquivo gerado aqui pode ser reimportado sem
 * perda de dados.
 *
 * @param array $properties  Linhas de `properties` (com city_name, state_code,
 *   neighborhood_name e photo_urls anexados por quem chama).
 */
function build_properties_xml(array $properties): string
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = true;

    $append = function (DOMElement $parent, string $tag, $value) use ($doc) {
        if ($value === null || $value === '') {
            return;
        }
        $el = $doc->createElement($tag);
        $el->appendChild($doc->createTextNode((string) $value));
        $parent->appendChild($el);
    };

    $carga = $doc->createElement('Carga');
    $doc->appendChild($carga);
    $imoveisRoot = $doc->createElement('Imoveis');
    $carga->appendChild($imoveisRoot);

    foreach ($properties as $p) {
        $imovel = $doc->createElement('Imovel');
        $imoveisRoot->appendChild($imovel);

        $append($imovel, 'CodigoImovel', $p['code']);
        $append($imovel, 'CodigoReferencia', $p['external_code'] ?: $p['code']);
        $append($imovel, 'Titulo', $p['title']);
        $append($imovel, 'Descricao', $p['description']);
        $append($imovel, 'Finalidade', $p['listing_type'] === 'RENT' ? 'Locação' : 'Venda');
        $append($imovel, 'TipoImovel', PROPERTY_TYPE_LABEL[$p['property_type']] ?? 'Outros Imóveis');
        $append($imovel, 'Status', $p['status'] === 'PUBLISHED' ? 'Ativo' : 'Inativo');

        $precos = $doc->createElement('Precos');
        $imovel->appendChild($precos);
        $append($precos, 'PrecoVenda', $p['price_sale']);
        $append($precos, 'PrecoLocacao', $p['price_rent']);
        $append($precos, 'PrecoCondominio', $p['condo_fee']);
        $append($precos, 'PrecoIptu', $p['iptu']);

        $caract = $doc->createElement('Caracteristicas');
        $imovel->appendChild($caract);
        $append($caract, 'AreaTotal', $p['total_area']);
        $append($caract, 'AreaConstruida', $p['built_area']);
        $append($caract, 'Quartos', $p['bedrooms']);
        $append($caract, 'Suites', $p['suites']);
        $append($caract, 'Banheiros', $p['bathrooms']);
        $append($caract, 'Vagas', $p['parking_spaces']);
        // xml_import_parser.php lê <Comodidade> como filhos diretos de
        // <Caracteristicas> (repetidos) — não dentro de um wrapper
        // <Comodidades>, senão o round-trip de importação perde essa lista.
        $features = json_decode_safe($p['features'] ?? null) ?: [];
        foreach ($features as $f) {
            $append($caract, 'Comodidade', $f);
        }

        $endereco = $doc->createElement('Endereco');
        $imovel->appendChild($endereco);
        $append($endereco, 'Logradouro', $p['street']);
        $append($endereco, 'Numero', $p['number']);
        $append($endereco, 'Bairro', $p['neighborhood_name'] ?? null);
        $append($endereco, 'Cidade', $p['city_name']);
        $append($endereco, 'Estado', $p['state_code'] ?? null);
        $append($endereco, 'CEP', $p['zip_code']);
        $append($endereco, 'Latitude', $p['latitude']);
        $append($endereco, 'Longitude', $p['longitude']);

        if (!empty($p['photo_urls'])) {
            $fotos = $doc->createElement('Fotos');
            $imovel->appendChild($fotos);
            foreach ($p['photo_urls'] as $url) {
                $append($fotos, 'Foto', $url);
            }
        }
    }

    return $doc->saveXML();
}

/**
 * Busca os dados extras (estado, bairro, fotos) que list_properties_for_advertiser()
 * não traz por padrão, e devolve os imóveis já prontos para build_properties_xml().
 */
function attach_xml_export_data(array $properties): array
{
    if (!$properties) {
        return [];
    }
    $pdo = db();

    $cityIds = array_values(array_unique(array_column($properties, 'city_id')));
    $placeholders = implode(',', array_fill(0, count($cityIds), '?'));
    $stmt = $pdo->prepare("SELECT id, state_code FROM cities WHERE id IN ($placeholders)");
    $stmt->execute($cityIds);
    $stateByCityId = array_column($stmt->fetchAll(), 'state_code', 'id');

    $neighborhoodIds = array_values(array_unique(array_filter(array_column($properties, 'neighborhood_id'))));
    $neighborhoodNameById = [];
    if ($neighborhoodIds) {
        $placeholders = implode(',', array_fill(0, count($neighborhoodIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM neighborhoods WHERE id IN ($placeholders)");
        $stmt->execute($neighborhoodIds);
        $neighborhoodNameById = array_column($stmt->fetchAll(), 'name', 'id');
    }

    $propertyIds = array_column($properties, 'id');
    $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
    $stmt = $pdo->prepare("SELECT property_id, url FROM property_images WHERE property_id IN ($placeholders) ORDER BY property_id, `order`");
    $stmt->execute($propertyIds);
    $photosByPropertyId = [];
    foreach ($stmt->fetchAll() as $row) {
        $photosByPropertyId[$row['property_id']][] = $row['url'];
    }

    foreach ($properties as &$p) {
        $p['state_code'] = $stateByCityId[$p['city_id']] ?? null;
        $p['neighborhood_name'] = $p['neighborhood_id'] ? ($neighborhoodNameById[$p['neighborhood_id']] ?? null) : null;
        $p['photo_urls'] = $photosByPropertyId[$p['id']] ?? [];
    }
    unset($p);

    return $properties;
}
