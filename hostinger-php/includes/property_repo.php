<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/constants.php';

/**
 * Monta WHERE + params a partir de filtros vindos da URL ($_GET), no mesmo
 * espírito da versão Next.js: query string -> SQL -> resultados.
 */
function build_property_filters(array $params, array $extra = []): array
{
    $where = ['p.status = "PUBLISHED"'];
    $args = [];

    foreach ($extra as $clause => $value) {
        $where[] = $clause;
        if (is_array($value)) {
            $args = array_merge($args, $value);
        } elseif ($value !== null) {
            $args[] = $value;
        }
    }

    if (!empty($params['cidade'])) {
        $where[] = 'c.slug = ?';
        $args[] = $params['cidade'];
    }
    if (!empty($params['bairro'])) {
        $where[] = 'n.slug = ?';
        $args[] = $params['bairro'];
    }
    if (!empty($params['transacao']) && isset(SLUG_TO_LISTING_TYPE[$params['transacao']])) {
        $where[] = 'p.listing_type = ?';
        $args[] = SLUG_TO_LISTING_TYPE[$params['transacao']];
    }
    if (!empty($params['tipo'])) {
        $type = array_search($params['tipo'], PROPERTY_TYPE_SLUG, true);
        if ($type) {
            $where[] = 'p.property_type = ?';
            $args[] = $type;
        }
    }
    if (!empty($params['imobiliaria'])) {
        $where[] = 'ag.slug = ?';
        $args[] = $params['imobiliaria'];
    }

    $priceField = ($params['transacao'] ?? '') === 'alugar' ? 'price_rent' : 'price_sale';
    if (!empty($params['precoMin'])) {
        $where[] = "p.$priceField >= ?";
        $args[] = (float) $params['precoMin'];
    }
    if (!empty($params['precoMax'])) {
        $where[] = "p.$priceField <= ?";
        $args[] = (float) $params['precoMax'];
    }
    foreach (['quartos' => 'bedrooms', 'suites' => 'suites', 'banheiros' => 'bathrooms', 'vagas' => 'parking_spaces'] as $param => $col) {
        if (!empty($params[$param])) {
            $where[] = "p.$col >= ?";
            $args[] = (int) $params[$param];
        }
    }
    if (!empty($params['areaMin'])) {
        $where[] = 'p.total_area >= ?';
        $args[] = (float) $params['areaMin'];
    }
    if (!empty($params['areaMax'])) {
        $where[] = 'p.total_area <= ?';
        $args[] = (float) $params['areaMax'];
    }
    if (!empty($params['q'])) {
        $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.code LIKE ? OR n.name LIKE ?)';
        $like = '%' . $params['q'] . '%';
        array_push($args, $like, $like, $like, $like);
    }

    return [implode(' AND ', $where), $args];
}

function build_property_order(?string $sort, ?string $transacao): string
{
    $priceField = $transacao === 'alugar' ? 'p.price_rent' : 'p.price_sale';
    return match ($sort) {
        'menor-preco' => "$priceField ASC",
        'maior-preco' => "$priceField DESC",
        'maior-area' => 'p.total_area DESC',
        default => 'p.published_at DESC',
    };
}

const PROPERTY_LIST_SELECT = "
    p.id, p.code, p.title, p.slug, p.listing_type, p.property_type, p.price_sale, p.price_rent,
    p.total_area, p.bedrooms, p.suites, p.bathrooms, p.parking_spaces, p.published_at,
    c.name AS city_name, c.slug AS city_slug, c.state_code,
    n.name AS neighborhood_name, n.slug AS neighborhood_slug,
    ag.name AS agency_name, ag.slug AS agency_slug
";

const PROPERTY_LIST_JOIN = "
    FROM properties p
    JOIN cities c ON c.id = p.city_id
    LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
    LEFT JOIN agencies ag ON ag.id = p.agency_id
";

function attach_primary_images(PDO $pdo, array $rows): array
{
    if (empty($rows)) {
        return $rows;
    }
    $ids = array_column($rows, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT property_id, url FROM property_images WHERE property_id IN ($placeholders) AND `order` = 0");
    $stmt->execute($ids);
    $images = [];
    foreach ($stmt->fetchAll() as $img) {
        $images[$img['property_id']] = $img['url'];
    }
    foreach ($rows as &$row) {
        $row['image_url'] = $images[$row['id']] ?? null;
    }
    return $rows;
}

function list_properties(array $params, array $extraWhere = []): array
{
    $pdo = db();
    [$whereSql, $args] = build_property_filters($params, $extraWhere);
    $orderSql = build_property_order($params['ordenar'] ?? null, $params['transacao'] ?? null);

    $page = max(1, (int) ($params['pagina'] ?? 1));
    $offset = ($page - 1) * PAGE_SIZE;

    $countStmt = $pdo->prepare('SELECT COUNT(*) ' . PROPERTY_LIST_JOIN . ' WHERE ' . $whereSql);
    $countStmt->execute($args);
    $total = (int) $countStmt->fetchColumn();

    $sql = 'SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . " WHERE $whereSql ORDER BY $orderSql LIMIT $offset, " . PAGE_SIZE;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $items = attach_primary_images($pdo, $stmt->fetchAll());

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'total_pages' => max(1, (int) ceil($total / PAGE_SIZE)),
    ];
}

function get_property_by_slug(string $slug): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT p.*, c.name AS city_name, c.slug AS city_slug, c.state_code, c.latitude AS city_lat, c.longitude AS city_lng,
               n.name AS neighborhood_name, n.slug AS neighborhood_slug,
               ag.name AS agency_name, ag.slug AS agency_slug, ag.phone AS agency_phone, ag.logo_url AS agency_logo,
               au.first_name AS advertiser_first_name, au.last_name AS advertiser_last_name, au.phone AS advertiser_phone, au.email AS advertiser_email,
               agu.first_name AS agent_first_name, agu.last_name AS agent_last_name, agu.phone AS agent_phone, agu.creci AS agent_creci
        FROM properties p
        JOIN cities c ON c.id = p.city_id
        LEFT JOIN neighborhoods n ON n.id = p.neighborhood_id
        LEFT JOIN agencies ag ON ag.id = p.agency_id
        JOIN users au ON au.id = p.advertiser_id
        LEFT JOIN users agu ON agu.id = p.agent_id
        WHERE p.slug = ?
    ');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['features'] = json_decode_safe($row['features']);

    $imgStmt = $pdo->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY `order` ASC');
    $imgStmt->execute([$row['id']]);
    $row['images'] = $imgStmt->fetchAll();

    return $row;
}

function get_property_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM properties WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $row['features'] = json_decode_safe($row['features']);
    }
    return $row ?: null;
}

function get_similar_properties(array $property): array
{
    $pdo = db();
    $items = [];

    if (!empty($property['neighborhood_id'])) {
        $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . '
            WHERE p.id != ? AND p.status = "PUBLISHED" AND p.neighborhood_id = ? AND p.property_type = ? AND p.listing_type = ?
            ORDER BY p.published_at DESC LIMIT 4');
        $stmt->execute([$property['id'], $property['neighborhood_id'], $property['property_type'], $property['listing_type']]);
        $items = $stmt->fetchAll();
    }

    if (count($items) < 4) {
        $need = 4 - count($items);
        $excludeIds = array_merge([$property['id']], array_column($items, 'id'));
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . "
            WHERE p.id NOT IN ($placeholders) AND p.status = \"PUBLISHED\" AND p.city_id = ? AND p.property_type = ? AND p.listing_type = ?
            ORDER BY p.published_at DESC LIMIT $need");
        $stmt->execute([...$excludeIds, $property['city_id'], $property['property_type'], $property['listing_type']]);
        $items = array_merge($items, $stmt->fetchAll());
    }

    return attach_primary_images($pdo, $items);
}

function get_featured_properties(int $limit = 6): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . " WHERE p.status = \"PUBLISHED\" ORDER BY p.published_at DESC LIMIT $limit");
    $stmt->execute();
    return attach_primary_images($pdo, $stmt->fetchAll());
}

function get_properties_by_city(string $citySlug, int $limit = 6): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT ' . PROPERTY_LIST_SELECT . PROPERTY_LIST_JOIN . "
        WHERE p.status = \"PUBLISHED\" AND c.slug = ? ORDER BY p.published_at DESC LIMIT $limit");
    $stmt->execute([$citySlug]);
    return attach_primary_images($pdo, $stmt->fetchAll());
}

function get_favorite_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT property_id FROM favorites WHERE user_id = ?');
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function property_href(array $property): string
{
    $listingSlug = LISTING_TYPE_SLUG[$property['listing_type']];
    $typeSlug = PROPERTY_TYPE_SLUG[$property['property_type']];
    return base_url("imovel.php?slug=" . urlencode($property['slug']));
}
