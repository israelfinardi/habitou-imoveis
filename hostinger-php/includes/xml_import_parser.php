<?php
/**
 * Parser de XML de imóveis no padrão VRSync — o formato mais usado pelos
 * CRMs imobiliários do mercado brasileiro (Vista, Union Softwares,
 * ImobiBrasil, Sublime, Kenlo, JetImob e outros normalmente têm uma opção
 * de "exportar XML no padrão VRSync"). Estrutura esperada:
 * <Carga><Imoveis><Imovel>...</Imovel></Imoveis></Carga>.
 *
 * Cada campo tenta algumas variações de nome de tag comuns entre CRMs
 * diferentes (ex.: Finalidade/FinalidadeTransacao/TipoOferta) antes de
 * desistir — ajuste os arrays de aliases abaixo se o XML de um CRM
 * específico usar um nome de tag diferente. O resto do sistema só usa o
 * array normalizado devolvido por parse_property_xml().
 */

const XML_IMPORT_TYPE_MAP = [
    'apartamento' => 'APARTMENT',
    'apto' => 'APARTMENT',
    'flat' => 'APARTMENT',
    'kitnet' => 'APARTMENT',
    'kitinete' => 'APARTMENT',
    'studio' => 'APARTMENT',
    'cobertura' => 'APARTMENT',
    'casa' => 'HOUSE',
    'casa em condominio' => 'HOUSE',
    'casa em condomínio' => 'HOUSE',
    'sobrado' => 'HOUSE',
    'terreno' => 'LAND',
    'lote' => 'LAND',
    'area' => 'LAND',
    'área' => 'LAND',
    'sala' => 'COMMERCIAL_ROOM',
    'sala/escritório' => 'COMMERCIAL_ROOM',
    'sala comercial' => 'COMMERCIAL_ROOM',
    'escritorio' => 'COMMERCIAL_ROOM',
    'escritório' => 'COMMERCIAL_ROOM',
    'conjunto comercial' => 'COMMERCIAL_ROOM',
    'loja' => 'STORE',
    'ponto comercial' => 'STORE',
    'galpao' => 'WAREHOUSE',
    'galpão' => 'WAREHOUSE',
    'deposito' => 'WAREHOUSE',
    'depósito' => 'WAREHOUSE',
    'barracao' => 'WAREHOUSE',
    'barracão' => 'WAREHOUSE',
    'imóvel rural' => 'RURAL',
    'imovel rural' => 'RURAL',
    'sitio' => 'RURAL',
    'sítio' => 'RURAL',
    'chacara' => 'RURAL',
    'chácara' => 'RURAL',
    'fazenda' => 'RURAL',
    'rancho' => 'RURAL',
    'predio' => 'BUILDING',
    'prédio' => 'BUILDING',
    'edificio' => 'BUILDING',
    'edifício' => 'BUILDING',
];

function xml_import_map_type(?string $raw): string
{
    if (!$raw) {
        return 'OTHER';
    }
    return XML_IMPORT_TYPE_MAP[mb_strtolower(trim($raw))] ?? 'OTHER';
}

function xml_import_map_listing(?string $raw): string
{
    $v = mb_strtolower(trim((string) $raw));
    if (str_contains($v, 'aluguel') || str_contains($v, 'locação') || str_contains($v, 'locacao') || str_contains($v, 'temporada') || $v === 'rent') {
        return 'RENT';
    }
    return 'SALE';
}

function xml_import_num($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    $str = trim((string) $value);
    if ($str === '') {
        return null;
    }

    $hasComma = str_contains($str, ',');
    $hasDot = str_contains($str, '.');

    if ($hasComma && $hasDot) {
        // Formato BR: "1.234.567,89" -> ponto = milhar, vírgula = decimal.
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
    } elseif ($hasComma) {
        // Só vírgula: assume separador decimal ("290000,00").
        $str = str_replace(',', '.', $str);
    }
    // Só ponto (ou nenhum separador): já é notação decimal padrão ("290000.00").

    return is_numeric($str) ? (float) $str : null;
}

function xml_import_text($node): ?string
{
    if ($node === null) {
        return null;
    }
    $s = trim((string) $node);
    return $s === '' ? null : $s;
}

/** Tenta várias tags candidatas (aliases de nome usados por CRMs diferentes) e devolve a primeira encontrada. */
function xml_import_first(?SimpleXMLElement $node, array $tags): ?SimpleXMLElement
{
    if (!$node) {
        return null;
    }
    foreach ($tags as $tag) {
        if (isset($node->{$tag}) && count($node->{$tag}) > 0) {
            return $node->{$tag};
        }
    }
    return null;
}

/**
 * @return array{listings: array, issues: array}
 */
function parse_property_xml(string $xml): array
{
    $issues = [];
    libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($doc === false) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $msg = $errors ? $errors[0]->message : 'XML malformado.';
        return ['listings' => [], 'issues' => [['message' => 'XML inválido: ' . trim($msg)]]];
    }

    $imoveisNode = xml_import_first($doc, ['Imoveis', 'imoveis', 'Properties', 'listings']) ?? $doc;
    $items = xml_import_first($imoveisNode, ['Imovel', 'imovel', 'Property', 'listing']) ?? [];

    if (count($items) === 0) {
        $issues[] = ['message' => 'Nenhum imóvel encontrado no XML (estrutura Carga/Imoveis/Imovel esperada — padrão VRSync).'];
    }

    $listings = [];
    foreach ($items as $item) {
        $externalId = xml_import_text(xml_import_first($item, ['CodigoImovel', 'Codigo', 'ListingID', 'Id', 'ID']));
        if (!$externalId) {
            $issues[] = ['message' => 'Imóvel ignorado: sem código externo (CodigoImovel).'];
            continue;
        }
        $title = xml_import_text(xml_import_first($item, ['Titulo', 'titulo', 'Title']));
        if (!$title) {
            $issues[] = ['externalId' => $externalId, 'message' => 'Imóvel ignorado: sem título.'];
            continue;
        }

        $precos = xml_import_first($item, ['Precos', 'Valores', 'Prices']);
        $caract = xml_import_first($item, ['Caracteristicas', 'Detalhes', 'Features']);
        $endereco = xml_import_first($item, ['Endereco', 'Localizacao', 'Address']);
        $fotos = xml_import_first($item, ['Fotos', 'Imagens', 'Photos', 'Midias']);

        $cidade = xml_import_text(xml_import_first($endereco, ['Cidade', 'City']));
        $estado = xml_import_text(xml_import_first($endereco, ['Estado', 'UF', 'State']));
        if (!$cidade || !$estado) {
            $issues[] = ['externalId' => $externalId, 'message' => 'Imóvel ignorado: endereço sem cidade/estado.'];
            continue;
        }

        $statusRaw = mb_strtolower((string) xml_import_text(xml_import_first($item, ['Status', 'Situacao'])));
        $active = $statusRaw ? !in_array($statusRaw, ['inativo', 'removido', 'inactive', 'removed', 'vendido', 'alugado'], true) : true;

        $photoUrls = [];
        if ($fotos) {
            $fotoItems = xml_import_first($fotos, ['Foto', 'Imagem', 'Photo']) ?? [];
            foreach ($fotoItems as $foto) {
                // Algumas fontes colocam a URL num atributo (URLArquivo) em
                // vez de texto direto do nó — tenta os dois. Acesso a
                // atributo inexistente em SimpleXML nunca devolve null (só
                // um elemento vazio), por isso o isset() explícito aqui.
                $url = xml_import_text($foto);
                if (!$url) {
                    if (isset($foto['URLArquivo'])) {
                        $url = xml_import_text($foto['URLArquivo']);
                    } elseif (isset($foto['url'])) {
                        $url = xml_import_text($foto['url']);
                    }
                }
                if ($url && preg_match('#^https?://#i', $url)) {
                    $photoUrls[] = $url;
                }
            }
        }

        $features = [];
        if ($caract) {
            $comItems = xml_import_first($caract, ['Comodidade', 'Comodidades', 'Amenities']) ?? [];
            foreach ($comItems as $com) {
                $f = xml_import_text($com);
                if ($f) {
                    $features[] = $f;
                }
            }
        }

        $listings[] = [
            'externalId' => $externalId,
            'externalCode' => xml_import_text(xml_import_first($item, ['CodigoReferencia', 'Referencia'])) ?? $externalId,
            'title' => $title,
            'description' => xml_import_text(xml_import_first($item, ['Descricao', 'Description'])),
            'listingType' => xml_import_map_listing(xml_import_text(xml_import_first($item, ['Finalidade', 'FinalidadeTransacao', 'TipoOferta', 'TransactionType']))),
            'propertyType' => xml_import_map_type(xml_import_text(xml_import_first($item, ['TipoImovel', 'Categoria', 'Tipo', 'Category']))),
            'priceSale' => $precos ? xml_import_num(xml_import_first($precos, ['PrecoVenda', 'ValorVenda', 'SalePrice'])) : null,
            'priceRent' => $precos ? xml_import_num(xml_import_first($precos, ['PrecoLocacao', 'PrecoAluguel', 'ValorLocacao', 'RentPrice'])) : null,
            'condoFee' => $precos ? xml_import_num(xml_import_first($precos, ['PrecoCondominio', 'ValorCondominio'])) : null,
            'iptu' => $precos ? xml_import_num(xml_import_first($precos, ['PrecoIptu', 'ValorIptu'])) : null,
            'totalArea' => $caract ? xml_import_num(xml_import_first($caract, ['AreaTotal', 'AreaTerreno'])) : null,
            'builtArea' => $caract ? xml_import_num(xml_import_first($caract, ['AreaConstruida', 'AreaPrivativa', 'AreaUtil'])) : null,
            'bedrooms' => $caract ? xml_import_num(xml_import_first($caract, ['Quartos', 'Dormitorios'])) : null,
            'suites' => $caract ? xml_import_num(xml_import_first($caract, ['Suites', 'Suítes'])) : null,
            'bathrooms' => $caract ? xml_import_num(xml_import_first($caract, ['Banheiros'])) : null,
            'parkingSpaces' => $caract ? xml_import_num(xml_import_first($caract, ['Vagas', 'VagasGaragem'])) : null,
            'features' => $features,
            'active' => $active,
            'address' => [
                'street' => xml_import_text(xml_import_first($endereco, ['Logradouro', 'Rua', 'Street'])),
                'number' => xml_import_text(xml_import_first($endereco, ['Numero', 'Number'])),
                'neighborhood' => xml_import_text(xml_import_first($endereco, ['Bairro', 'Neighborhood'])),
                'city' => $cidade,
                'state' => $estado,
                'zipCode' => xml_import_text(xml_import_first($endereco, ['CEP', 'ZipCode'])),
                'latitude' => $endereco ? xml_import_num(xml_import_first($endereco, ['Latitude'])) : null,
                'longitude' => $endereco ? xml_import_num(xml_import_first($endereco, ['Longitude'])) : null,
            ],
            'photos' => $photoUrls,
        ];
    }

    return ['listings' => $listings, 'issues' => $issues];
}
