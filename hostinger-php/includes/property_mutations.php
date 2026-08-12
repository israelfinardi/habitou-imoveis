<?php
require_once __DIR__ . '/../config/database.php';

function get_or_create_neighborhood(int $cityId, string $name): int
{
    $pdo = db();
    $slug = slugify($name) ?: 'sem-bairro';
    $stmt = $pdo->prepare('SELECT id FROM neighborhoods WHERE city_id = ? AND slug = ?');
    $stmt->execute([$cityId, $slug]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $pdo->prepare('INSERT INTO neighborhoods (city_id, name, slug) VALUES (?,?,?)')->execute([$cityId, $name, $slug]);
    return (int) $pdo->lastInsertId();
}

function get_city_id_by_slug(string $slug): ?int
{
    $stmt = db()->prepare('SELECT id FROM cities WHERE slug = ?');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

/**
 * Recebe "Nome da Cidade (UF)" (formato do seletor de localização do
 * cadastro, com todas as ~5.600 cidades do Brasil) e retorna o id da
 * cidade, criando a linha em `cities` sob demanda se ainda não existir.
 */
function get_or_create_city(string $label): ?int
{
    $parsed = parse_city_label($label);
    if (!$parsed) {
        return null;
    }
    [$name, $uf, $slug] = $parsed;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM cities WHERE slug = ?');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    $pdo->prepare('INSERT INTO cities (name, slug, state, state_code, region) VALUES (?,?,?,?,?)')
        ->execute([$name, $slug, BRAZIL_STATES[$uf], $uf, BRAZIL_REGIONS[$uf] ?? null]);
    return (int) $pdo->lastInsertId();
}

/**
 * Só resolve o slug (sem tocar no banco) a partir de "Nome da Cidade (UF)" —
 * usado pra filtrar a busca por uma cidade que ainda pode não ter nenhuma
 * linha em `cities` (nesse caso o slug resolvido simplesmente não bate com
 * nada e a busca retorna zero resultados, sem erro).
 */
function parse_city_label(string $label): ?array
{
    if (!preg_match('/^(.+?)\s*\(([A-Za-z]{2})\)\s*$/u', trim($label), $m)) {
        return null;
    }
    $name = trim($m[1]);
    $uf = mb_strtoupper($m[2]);
    if ($name === '' || !isset(BRAZIL_STATES[$uf])) {
        return null;
    }

    // Reaproveita o slug já usado pelas cidades em destaque de SC (sem sufixo de UF),
    // pra não quebrar os links de navegação que já apontam pra eles.
    foreach (FEATURED_CITIES as $fc) {
        if ($fc['state_code'] === $uf && mb_strtolower($fc['name']) === mb_strtolower($name)) {
            return [$name, $uf, $fc['slug']];
        }
    }
    return [$name, $uf, slugify($name) . '-' . strtolower($uf)];
}

function resolve_city_slug(string $label): ?string
{
    $parsed = parse_city_label($label);
    return $parsed ? $parsed[2] : null;
}

function create_property(array $input, array $actor): int
{
    $cityId = get_or_create_city($input['cidade']);
    if (!$cityId) {
        throw new \InvalidArgumentException('Cidade inválida.');
    }
    $cityRow = db()->prepare('SELECT name FROM cities WHERE id = ?');
    $cityRow->execute([$cityId]);
    $cityName = $cityRow->fetchColumn();

    $neighborhoodId = get_or_create_neighborhood($cityId, $input['bairro']);

    $pdo = db();
    $code = next_property_code($pdo);
    $suffix = substr(bin2hex(random_bytes(4)), 0, 6);
    $slug = slugify($input['title'] . ' ' . $input['bairro'] . ' ' . $cityName) . '-' . $suffix;

    $agencyId = in_array($actor['role'], ['AGENCY_ADMIN', 'AGENT'], true) ? $actor['agency_id'] : null;

    $stmt = $pdo->prepare('INSERT INTO properties
        (code, title, slug, description, listing_type, property_type, price_sale, price_rent, condo_fee, iptu,
         total_area, built_area, bedrooms, suites, bathrooms, parking_spaces, features, status,
         city_id, neighborhood_id, street, number, complement, zip_code, latitude, longitude, advertiser_id, agency_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"DRAFT",?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $code, $input['title'], $slug, $input['description'] ?: null, $input['listingType'], $input['propertyType'],
        $input['priceSale'] ?: null, $input['priceRent'] ?: null, $input['condoFee'] ?: null, $input['iptu'] ?: null,
        $input['totalArea'] ?: null, $input['builtArea'] ?: null, $input['bedrooms'] ?: null, $input['suites'] ?: null,
        $input['bathrooms'] ?: null, $input['parkingSpaces'] ?: null, json_encode($input['features'] ?? [], JSON_UNESCAPED_UNICODE),
        $cityId, $neighborhoodId, $input['street'] ?: null, $input['number'] ?: null, $input['complement'] ?: null,
        $input['zipCode'] ?: null, $input['latitude'] ?: null, $input['longitude'] ?: null, $actor['id'], $agencyId,
    ]);
    return (int) $pdo->lastInsertId();
}

function update_property(int $propertyId, array $input, array $actor): void
{
    $property = get_property_by_id($propertyId);
    if (!$property || !can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode editar este imóvel.');
    }

    $cityId = get_or_create_city($input['cidade']);
    if (!$cityId) {
        throw new \InvalidArgumentException('Cidade inválida.');
    }
    $neighborhoodId = get_or_create_neighborhood($cityId, $input['bairro']);

    $stmt = db()->prepare('UPDATE properties SET title=?, description=?, listing_type=?, property_type=?, price_sale=?, price_rent=?,
        condo_fee=?, iptu=?, total_area=?, built_area=?, bedrooms=?, suites=?, bathrooms=?, parking_spaces=?, features=?,
        city_id=?, neighborhood_id=?, street=?, number=?, complement=?, zip_code=?, latitude=?, longitude=? WHERE id=?');
    $stmt->execute([
        $input['title'], $input['description'] ?: null, $input['listingType'], $input['propertyType'],
        $input['priceSale'] ?: null, $input['priceRent'] ?: null, $input['condoFee'] ?: null, $input['iptu'] ?: null,
        $input['totalArea'] ?: null, $input['builtArea'] ?: null, $input['bedrooms'] ?: null, $input['suites'] ?: null,
        $input['bathrooms'] ?: null, $input['parkingSpaces'] ?: null, json_encode($input['features'] ?? [], JSON_UNESCAPED_UNICODE),
        $cityId, $neighborhoodId, $input['street'] ?: null, $input['number'] ?: null, $input['complement'] ?: null,
        $input['zipCode'] ?: null, $input['latitude'] ?: null, $input['longitude'] ?: null, $propertyId,
    ]);
}

function delete_property(int $propertyId, array $actor): void
{
    $property = get_property_by_id($propertyId);
    if (!$property || !can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode excluir este imóvel.');
    }
    db()->prepare('DELETE FROM properties WHERE id = ?')->execute([$propertyId]);
}

function set_property_status(int $propertyId, string $action, array $actor): void
{
    $property = get_property_by_id($propertyId);
    if (!$property || !can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode alterar este imóvel.');
    }
    $map = ['publish' => 'PUBLISHED', 'reactivate' => 'PUBLISHED', 'pause' => 'PAUSED', 'archive' => 'ARCHIVED'];
    $status = $map[$action] ?? null;
    if (!$status) {
        return;
    }
    $pdo = db();
    if ($status === 'PUBLISHED' && !$property['published_at']) {
        $pdo->prepare('UPDATE properties SET status=?, published_at=NOW() WHERE id=?')->execute([$status, $propertyId]);
    } elseif ($status === 'ARCHIVED') {
        $pdo->prepare('UPDATE properties SET status=?, deactivated_at=NOW() WHERE id=?')->execute([$status, $propertyId]);
    } else {
        $pdo->prepare('UPDATE properties SET status=? WHERE id=?')->execute([$status, $propertyId]);
    }
}

function duplicate_property(int $propertyId, array $actor): int
{
    $property = get_property_by_id($propertyId);
    if (!$property || !can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode duplicar este imóvel.');
    }
    $pdo = db();
    $code = next_property_code($pdo);
    $slug = slugify($property['title'] . ' copia') . '-' . substr(bin2hex(random_bytes(4)), 0, 6);

    $stmt = $pdo->prepare('INSERT INTO properties
        (code, title, slug, description, listing_type, property_type, price_sale, price_rent, condo_fee, iptu,
         total_area, built_area, bedrooms, suites, bathrooms, parking_spaces, features, status,
         city_id, neighborhood_id, street, number, complement, zip_code, latitude, longitude, advertiser_id, agency_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"DRAFT",?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $code, $property['title'] . ' (cópia)', $slug, $property['description'], $property['listing_type'], $property['property_type'],
        $property['price_sale'], $property['price_rent'], $property['condo_fee'], $property['iptu'],
        $property['total_area'], $property['built_area'], $property['bedrooms'], $property['suites'],
        $property['bathrooms'], $property['parking_spaces'], json_encode($property['features'], JSON_UNESCAPED_UNICODE),
        $property['city_id'], $property['neighborhood_id'], $property['street'], $property['number'], $property['complement'],
        $property['zip_code'], $property['latitude'], $property['longitude'], $actor['id'], $property['agency_id'],
    ]);
    $newId = (int) $pdo->lastInsertId();

    $imgStmt = $pdo->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY `order`');
    $imgStmt->execute([$propertyId]);
    $insertImg = $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin) VALUES (?,?,?,?,?)');
    foreach ($imgStmt->fetchAll() as $img) {
        $insertImg->execute([$newId, $img['url'], $img['order'], $img['is_primary'], $img['origin']]);
    }

    return $newId;
}

function list_properties_for_advertiser(array $actor): array
{
    $pdo = db();
    if ($actor['role'] === 'ADMIN') {
        $stmt = $pdo->query('SELECT p.*, c.name AS city_name FROM properties p JOIN cities c ON c.id = p.city_id ORDER BY p.updated_at DESC');
    } elseif (!empty($actor['agency_id'])) {
        $stmt = $pdo->prepare('SELECT p.*, c.name AS city_name FROM properties p JOIN cities c ON c.id = p.city_id WHERE p.advertiser_id = ? OR p.agency_id = ? ORDER BY p.updated_at DESC');
        $stmt->execute([$actor['id'], $actor['agency_id']]);
    } else {
        $stmt = $pdo->prepare('SELECT p.*, c.name AS city_name FROM properties p JOIN cities c ON c.id = p.city_id WHERE p.advertiser_id = ? ORDER BY p.updated_at DESC');
        $stmt->execute([$actor['id']]);
    }
    return attach_primary_images($pdo, $stmt->fetchAll());
}

// --- Fotos ---------------------------------------------------------------

function add_property_image(int $propertyId, string $url, array $actor, ?int $sizeBytes = null): int
{
    $property = get_property_by_id($propertyId);
    if (!$property || !can_manage_property($actor, $property)) {
        throw new \RuntimeException('Você não pode editar fotos deste imóvel.');
    }
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = ?');
    $stmt->execute([$propertyId]);
    $existingCount = (int) $stmt->fetchColumn();

    $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin, size_bytes) VALUES (?,?,?,?,"MANUAL",?)')
        ->execute([$propertyId, $url, $existingCount, $existingCount === 0 ? 1 : 0, $sizeBytes]);
    return (int) $pdo->lastInsertId();
}

function remove_property_image(int $imageId, array $actor): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT pi.*, p.advertiser_id, p.agency_id FROM property_images pi JOIN properties p ON p.id = pi.property_id WHERE pi.id = ?');
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    if (!$image || !can_manage_property($actor, $image)) {
        throw new \RuntimeException('Você não pode remover esta foto.');
    }
    $pdo->prepare('DELETE FROM property_images WHERE id = ?')->execute([$imageId]);
    if ($image['is_primary']) {
        $next = $pdo->prepare('SELECT id FROM property_images WHERE property_id = ? ORDER BY `order` LIMIT 1');
        $next->execute([$image['property_id']]);
        $nextId = $next->fetchColumn();
        if ($nextId) {
            $pdo->prepare('UPDATE property_images SET is_primary = 1 WHERE id = ?')->execute([$nextId]);
        }
    }
}

function set_primary_image(int $imageId, array $actor): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT pi.*, p.advertiser_id, p.agency_id FROM property_images pi JOIN properties p ON p.id = pi.property_id WHERE pi.id = ?');
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    if (!$image || !can_manage_property($actor, $image)) {
        throw new \RuntimeException('Você não pode editar fotos deste imóvel.');
    }
    $pdo->prepare('UPDATE property_images SET is_primary = 0 WHERE property_id = ?')->execute([$image['property_id']]);
    $pdo->prepare('UPDATE property_images SET is_primary = 1 WHERE id = ?')->execute([$imageId]);
}
