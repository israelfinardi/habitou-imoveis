<?php
/**
 * Parser para feeds no formato VRSync (XML), mesmo formato usado na versão
 * Next.js (src/server/vrsync/parser.ts). Segue a convenção comum de feeds
 * imobiliários brasileiros: <Carga><Imoveis><Imovel>...
 * Ajuste apenas este arquivo quando a especificação exata do feed do
 * cliente for diferente — o resto do sistema só usa o array normalizado.
 */

const VRSYNC_TYPE_MAP = [
    'apartamento' => 'APARTMENT',
    'casa' => 'HOUSE',
    'terreno' => 'LAND',
    'sala/escritório' => 'COMMERCIAL_ROOM',
    'sala comercial' => 'COMMERCIAL_ROOM',
    'escritorio' => 'COMMERCIAL_ROOM',
    'loja' => 'STORE',
    'galpao' => 'WAREHOUSE',
    'galpão' => 'WAREHOUSE',
    'imóvel rural' => 'RURAL',
    'imovel rural' => 'RURAL',
    'sitio' => 'RURAL',
    'sítio' => 'RURAL',
    'chacara' => 'RURAL',
    'chácara' => 'RURAL',
    'fazenda' => 'RURAL',
    'predio' => 'BUILDING',
    'prédio' => 'BUILDING',
    'cobertura' => 'APARTMENT',
];

function vrsync_map_type(?string $raw): string
{
    if (!$raw) return 'OTHER';
    return VRSYNC_TYPE_MAP[mb_strtolower(trim($raw))] ?? 'OTHER';
}

function vrsync_map_listing(?string $raw): string
{
    $v = mb_strtolower(trim((string) $raw));
    if (str_contains($v, 'aluguel') || str_contains($v, 'locação') || str_contains($v, 'locacao') || $v === 'rent') {
        return 'RENT';
    }
    return 'SALE';
}

function vrsync_num($value): ?float
{
    if ($value === null || $value === '') return null;
    $str = trim((string) $value);
    if ($str === '') return null;

    $hasComma = str_contains($str, ',');
    $hasDot = str_contains($str, '.');

    if ($hasComma && $hasDot) {
        // Formato BR: "1.234.567,89" -> ponto = milhar, vírgula = decimal
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
    } elseif ($hasComma) {
        // Só vírgula: assume separador decimal ("290000,00")
        $str = str_replace(',', '.', $str);
    }
    // Só ponto (ou nenhum separador): já é notação decimal padrão ("290000.00"), usar como está.

    return is_numeric($str) ? (float) $str : null;
}

function vrsync_text($node): ?string
{
    if ($node === null) return null;
    $s = trim((string) $node);
    return $s === '' ? null : $s;
}

/**
 * @return array{listings: array, issues: array}
 */
function parse_vrsync_xml(string $xml): array
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

    $imoveisNode = $doc->Imoveis ?? $doc->imoveis ?? $doc;
    $items = $imoveisNode->Imovel ?? $imoveisNode->imovel ?? [];

    if (count($items) === 0) {
        $issues[] = ['message' => 'Nenhum imóvel encontrado no feed (estrutura Carga/Imoveis/Imovel esperada).'];
    }

    $listings = [];
    foreach ($items as $item) {
        $externalId = vrsync_text($item->CodigoImovel ?? $item->Codigo ?? $item->Id ?? null);
        if (!$externalId) {
            $issues[] = ['message' => 'Imóvel ignorado: sem código externo (CodigoImovel).'];
            continue;
        }
        $title = vrsync_text($item->Titulo ?? $item->titulo ?? null);
        if (!$title) {
            $issues[] = ['externalId' => $externalId, 'message' => 'Imóvel ignorado: sem título.'];
            continue;
        }

        $precos = $item->Precos ?? null;
        $caract = $item->Caracteristicas ?? null;
        $endereco = $item->Endereco ?? null;
        $fotos = $item->Fotos ?? null;

        $cidade = vrsync_text($endereco->Cidade ?? null);
        $estado = vrsync_text($endereco->Estado ?? $endereco->UF ?? null);
        if (!$cidade || !$estado) {
            $issues[] = ['externalId' => $externalId, 'message' => 'Imóvel ignorado: endereço sem cidade/estado.'];
            continue;
        }

        $statusRaw = mb_strtolower((string) vrsync_text($item->Status ?? null));
        $active = $statusRaw ? !in_array($statusRaw, ['inativo', 'removido', 'inactive', 'removed'], true) : true;

        $photoUrls = [];
        if ($fotos) {
            foreach ($fotos->Foto ?? [] as $foto) {
                $url = vrsync_text($foto);
                if ($url && preg_match('#^https?://#', $url)) {
                    $photoUrls[] = $url;
                }
            }
        }

        $features = [];
        if ($caract) {
            foreach ($caract->Comodidade ?? [] as $com) {
                $f = vrsync_text($com);
                if ($f) $features[] = $f;
            }
        }

        $listings[] = [
            'externalId' => $externalId,
            'externalCode' => vrsync_text($item->CodigoReferencia ?? null) ?? $externalId,
            'title' => $title,
            'description' => vrsync_text($item->Descricao ?? null),
            'listingType' => vrsync_map_listing(vrsync_text($item->Finalidade ?? null)),
            'propertyType' => vrsync_map_type(vrsync_text($item->TipoImovel ?? $item->Tipo ?? null)),
            'priceSale' => $precos ? vrsync_num($precos->PrecoVenda ?? null) : null,
            'priceRent' => $precos ? vrsync_num($precos->PrecoLocacao ?? $precos->PrecoAluguel ?? null) : null,
            'condoFee' => $precos ? vrsync_num($precos->PrecoCondominio ?? null) : null,
            'iptu' => $precos ? vrsync_num($precos->PrecoIptu ?? null) : null,
            'totalArea' => $caract ? vrsync_num($caract->AreaTotal ?? null) : null,
            'builtArea' => $caract ? vrsync_num($caract->AreaConstruida ?? null) : null,
            'bedrooms' => $caract ? vrsync_num($caract->Quartos ?? $caract->Dormitorios ?? null) : null,
            'suites' => $caract ? vrsync_num($caract->Suites ?? null) : null,
            'bathrooms' => $caract ? vrsync_num($caract->Banheiros ?? null) : null,
            'parkingSpaces' => $caract ? vrsync_num($caract->Vagas ?? null) : null,
            'features' => $features,
            'active' => $active,
            'address' => [
                'street' => vrsync_text($endereco->Logradouro ?? $endereco->Rua ?? null),
                'number' => vrsync_text($endereco->Numero ?? null),
                'neighborhood' => vrsync_text($endereco->Bairro ?? null),
                'city' => $cidade,
                'state' => $estado,
                'zipCode' => vrsync_text($endereco->CEP ?? null),
                'latitude' => $endereco ? vrsync_num($endereco->Latitude ?? null) : null,
                'longitude' => $endereco ? vrsync_num($endereco->Longitude ?? null) : null,
            ],
            'photos' => $photoUrls,
        ];
    }

    return ['listings' => $listings, 'issues' => $issues];
}
