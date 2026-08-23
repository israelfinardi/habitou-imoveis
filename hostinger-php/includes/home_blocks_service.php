<?php
require_once __DIR__ . '/recommendation_service.php';

/**
 * Blocos de sugestão da home estilo Airbnb — cada função aqui devolve os
 * itens de UM carrossel (includes/property_card.php::render_home_carousel).
 * "Destaque na sua região" reaproveita query_properties_near(), já em
 * recommendation_service.php; os outros 4 blocos estão aqui.
 */

const GOOD_VALUE_MIN_DISCOUNT_PCT = 0.15; // 15% abaixo da média já conta como "bom valor"
const GOOD_VALUE_MIN_SAMPLE_SIZE = 3;      // amostra mínima pra confiar na média de um bairro/cidade
const POI_SEARCH_RADIUS_KM = 3.0;          // "perto" de um ponto de interesse = raio pequeno, de vizinhança
const NEARBY_CITIES_RADIUS_KM = 80.0;      // região metropolitana

/**
 * "Vistos recentemente": os IDs vêm do localStorage do navegador (ver
 * assets/js/home-blocks.js) — não há tabela server-side pra isso, é
 * histórico local por dispositivo, igual ao conceito do Airbnb. Aqui só
 * valida que os imóveis ainda existem/estão publicados e devolve na MESMA
 * ordem recebida (mais recente primeiro, como o cliente já mandou).
 */
function get_recently_viewed_properties(array $propertyIds, int $limit = 12): array
{
    $ids = array_values(array_unique(array_map('intval', $propertyIds)));
    $ids = array_slice($ids, 0, $limit);
    if (!$ids) {
        return [];
    }
    $pdo = db();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . " WHERE p.id IN ($placeholders) AND p.status = 'PUBLISHED'");
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int) $row['id']] = $row;
    }
    // Reordena pela ordem recebida (a query IN() não garante ordem) e
    // descarta silenciosamente os IDs que não existem mais/despublicaram.
    $ordered = array_values(array_filter(array_map(fn ($id) => $byId[$id] ?? null, $ids)));
    return attach_primary_images($pdo, $ordered, 1);
}

/**
 * "Perto de pontos de interesse": entre os POIs cadastrados na cidade de
 * referência, escolhe o que tem mais imóveis publicados num raio pequeno
 * (bairro/vizinhança) e devolve os mais próximos dele. Sem POI cadastrado
 * na cidade, ou nenhum com imóveis suficientes por perto, devolve null —
 * o bloco inteiro simplesmente não aparece na home (como no Airbnb).
 * @return ?array{poi: array, items: array}
 */
function get_nearby_poi_properties(?int $cityId, ?string $listingType, int $limit = 12): ?array
{
    if (!$cityId) {
        return null;
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM points_of_interest WHERE city_id = ?');
    $stmt->execute([$cityId]);
    $pois = $stmt->fetchAll();
    if (!$pois) {
        return null;
    }

    $best = null;
    foreach ($pois as $poi) {
        $items = query_properties_near($pdo, (float) $poi['latitude'], (float) $poi['longitude'], POI_SEARCH_RADIUS_KM, $listingType, [], $limit);
        if (count($items) >= 3 && (!$best || count($items) > count($best['items']))) {
            $best = ['poi' => $poi, 'items' => $items];
        }
    }
    return $best;
}

/**
 * "Oportunidades e bom valor": compara o preço de cada imóvel com a média
 * do próprio bairro (ou da cidade toda, se o bairro não tiver amostra
 * suficiente pra média confiável) e destaca os que estão pelo menos
 * GOOD_VALUE_MIN_DISCOUNT_PCT abaixo — ordenado pelo maior desconto
 * primeiro. Sem funções de janela (window functions podem não estar
 * disponíveis no SQLite da Hostinger): duas consultas simples (médias, depois
 * candidatos) e a comparação em PHP, mais portátil.
 */
function get_good_value_properties(?int $cityId, string $listingType, int $limit = 12): array
{
    if (!$cityId) {
        return [];
    }
    $pdo = db();
    $priceField = $listingType === 'RENT' ? 'price_rent' : 'price_sale';

    $stmt = $pdo->prepare("SELECT neighborhood_id, AVG($priceField) AS avg_price, COUNT(*) AS n
        FROM properties WHERE city_id = ? AND listing_type = ? AND status = 'PUBLISHED' AND $priceField IS NOT NULL AND neighborhood_id IS NOT NULL
        GROUP BY neighborhood_id");
    $stmt->execute([$cityId, $listingType]);
    $neighborhoodAvg = [];
    foreach ($stmt->fetchAll() as $row) {
        if ((int) $row['n'] >= GOOD_VALUE_MIN_SAMPLE_SIZE) {
            $neighborhoodAvg[(int) $row['neighborhood_id']] = (float) $row['avg_price'];
        }
    }

    $stmt = $pdo->prepare("SELECT AVG($priceField) AS avg_price, COUNT(*) AS n
        FROM properties WHERE city_id = ? AND listing_type = ? AND status = 'PUBLISHED' AND $priceField IS NOT NULL");
    $stmt->execute([$cityId, $listingType]);
    $cityRow = $stmt->fetch();
    $cityAvg = ($cityRow && (int) $cityRow['n'] >= GOOD_VALUE_MIN_SAMPLE_SIZE) ? (float) $cityRow['avg_price'] : null;

    if (!$neighborhoodAvg && !$cityAvg) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN
        . " WHERE p.city_id = ? AND p.listing_type = ? AND p.status = 'PUBLISHED' AND p.$priceField IS NOT NULL");
    $stmt->execute([$cityId, $listingType]);
    $candidates = $stmt->fetchAll();

    $withDiscount = [];
    foreach ($candidates as $row) {
        $reference = $neighborhoodAvg[(int) $row['neighborhood_id']] ?? $cityAvg;
        if (!$reference) {
            continue;
        }
        $price = (float) $row[$priceField];
        $discount = 1 - ($price / $reference);
        if ($discount >= GOOD_VALUE_MIN_DISCOUNT_PCT) {
            $row['_discount_pct'] = $discount;
            $withDiscount[] = $row;
        }
    }
    usort($withDiscount, fn ($a, $b) => $b['_discount_pct'] <=> $a['_discount_pct']);
    return attach_primary_images($pdo, array_slice($withDiscount, 0, $limit), 1);
}

/**
 * "Explore cidades vizinhas": outras cidades com imóveis publicados dentro
 * de NEARBY_CITIES_RADIUS_KM da cidade de referência (mesma região
 * metropolitana), pra cross-selling quando a busca do usuário é muito
 * específica de uma cidade só. Reaproveita haversine_distance_km() já
 * usado no algoritmo de geolocalização.
 * @return array<array{id:int, name:string, slug:string, state_code:string, property_count:int, distance_km:float}>
 */
function get_nearby_cities(int $cityId, int $limit = 8): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT latitude, longitude FROM cities WHERE id = ?');
    $stmt->execute([$cityId]);
    $ref = $stmt->fetch();
    if (!$ref || !$ref['latitude'] || !$ref['longitude']) {
        return [];
    }
    $refLat = (float) $ref['latitude'];
    $refLng = (float) $ref['longitude'];

    $latDelta = NEARBY_CITIES_RADIUS_KM / 111.0;
    $lngDelta = NEARBY_CITIES_RADIUS_KM / (111.0 * max(cos(deg2rad($refLat)), 0.1));

    $stmt = $pdo->prepare("SELECT c.id, c.name, c.slug, c.state_code, c.latitude, c.longitude, COUNT(p.id) AS property_count
        FROM cities c JOIN properties p ON p.city_id = c.id AND p.status = 'PUBLISHED'
        WHERE c.id != ? AND c.latitude BETWEEN ? AND ? AND c.longitude BETWEEN ? AND ?
        GROUP BY c.id HAVING property_count > 0");
    $stmt->execute([$cityId, $refLat - $latDelta, $refLat + $latDelta, $refLng - $lngDelta, $refLng + $lngDelta]);
    $candidates = $stmt->fetchAll();

    foreach ($candidates as &$row) {
        $row['distance_km'] = haversine_distance_km($refLat, $refLng, (float) $row['latitude'], (float) $row['longitude']);
    }
    unset($row);

    $candidates = array_values(array_filter($candidates, fn ($c) => $c['distance_km'] <= NEARBY_CITIES_RADIUS_KM));
    usort($candidates, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    return array_slice($candidates, 0, $limit);
}

/**
 * Resolve a cidade de referência pra montar os blocos "perto de POI",
 * "bom valor" e "cidades vizinhas": a última cidade buscada pelo usuário
 * logado (search_history), senão a cidade mais próxima da localização da
 * sessão (GPS/IP já resolvidos por get_session_location), senão null (os
 * blocos que dependem de cidade simplesmente não aparecem).
 */
function resolve_reference_city_id(?array $user, string $sessionId, string $ip): ?int
{
    $pdo = db();
    if ($user) {
        $stmt = $pdo->prepare("SELECT city_id FROM search_history WHERE user_id = ? AND city_id IS NOT NULL ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([(int) $user['id']]);
        $cityId = $stmt->fetchColumn();
        if ($cityId) {
            return (int) $cityId;
        }
    }

    $location = get_session_location($sessionId, $user ? (int) $user['id'] : null, $ip);
    if (!$location) {
        return null;
    }
    // Cidade cadastrada mais próxima da localização resolvida — mesma
    // lógica de caixa delimitadora usada em query_properties_near(), só
    // que aplicada à tabela cities em vez de properties.
    $latDelta = 100 / 111.0;
    $lngDelta = 100 / (111.0 * max(cos(deg2rad($location['lat'])), 0.1));
    $stmt = $pdo->prepare('SELECT id, latitude, longitude FROM cities WHERE latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?');
    $stmt->execute([$location['lat'] - $latDelta, $location['lat'] + $latDelta, $location['lng'] - $lngDelta, $location['lng'] + $lngDelta]);
    $closest = null;
    $closestDistance = null;
    foreach ($stmt->fetchAll() as $c) {
        $d = haversine_distance_km($location['lat'], $location['lng'], (float) $c['latitude'], (float) $c['longitude']);
        if ($closestDistance === null || $d < $closestDistance) {
            $closestDistance = $d;
            $closest = (int) $c['id'];
        }
    }
    return $closest;
}
