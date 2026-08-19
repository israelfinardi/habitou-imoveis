<?php
require_once __DIR__ . '/vrsync_parser.php';
require_once __DIR__ . '/property_mutations.php';

class VRSyncFetchError extends \RuntimeException {}

function vrsync_fetch_xml(string $url): string
{
    $context = stream_context_create([
        'http' => ['timeout' => 20, 'header' => "Accept: application/xml,text/xml\r\n"],
        'https' => ['timeout' => 20, 'header' => "Accept: application/xml,text/xml\r\n"],
    ]);
    $xml = @file_get_contents($url, false, $context);
    if ($xml === false) {
        throw new VRSyncFetchError('Falha ao acessar o feed (timeout ou URL inválida).');
    }
    if (strlen($xml) > 50 * 1024 * 1024) {
        throw new VRSyncFetchError('Feed excede o tamanho máximo permitido (50MB).');
    }
    return $xml;
}

function vrsync_resolve_location(array $listing): array
{
    $citySlug = slugify($listing['address']['city']);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM cities WHERE slug = ?');
    $stmt->execute([$citySlug]);
    $cityId = $stmt->fetchColumn();
    if (!$cityId) {
        $stateCode = mb_strlen($listing['address']['state']) === 2 ? mb_strtoupper($listing['address']['state']) : mb_strtoupper(mb_substr($listing['address']['state'], 0, 2));
        $pdo->prepare('INSERT INTO cities (name, slug, state, state_code, region) VALUES (?,?,?,?,?)')
            ->execute([$listing['address']['city'], $citySlug, $listing['address']['state'], $stateCode, BRAZIL_REGIONS[$stateCode] ?? null]);
        $cityId = (int) $pdo->lastInsertId();
    }

    $neighborhoodId = null;
    if (!empty($listing['address']['neighborhood'])) {
        $neighborhoodId = get_or_create_neighborhood((int) $cityId, $listing['address']['neighborhood']);
    }

    return [(int) $cityId, $neighborhoodId];
}

function vrsync_ensure_feed_advertiser(int $agencyId): int
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE agency_id = ? AND role = "AGENCY_ADMIN" LIMIT 1');
    $stmt->execute([$agencyId]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    $agStmt = $pdo->prepare('SELECT name, slug FROM agencies WHERE id = ?');
    $agStmt->execute([$agencyId]);
    $agency = $agStmt->fetch();
    $email = 'vrsync+' . $agency['slug'] . '@habitou.com.br';
    $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, status, agency_id) VALUES (?,?,?,?,"AGENCY_ADMIN","ACTIVE",?)
        ON CONFLICT(email) DO UPDATE SET agency_id = excluded.agency_id')
        ->execute([explode(' ', $agency['name'])[0] ?: 'Imobiliária', 'VRSync', $email, hash_password(bin2hex(random_bytes(16))), $agencyId]);
    $stmt->execute([$agencyId]);
    return (int) $stmt->fetchColumn();
}

function run_feed_sync(int $feedId): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM feeds WHERE id = ?');
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();
    if (!$feed) {
        throw new \RuntimeException('Feed não encontrado.');
    }

    $pdo->prepare('INSERT INTO feed_sync_logs (feed_id, status) VALUES (?, "RUNNING")')->execute([$feedId]);
    $logId = (int) $pdo->lastInsertId();

    $totalFound = 0; $totalCreated = 0; $totalUpdated = 0; $totalDeactivated = 0; $totalUnchanged = 0; $totalErrors = 0;
    $errorMessage = null;

    try {
        $xml = vrsync_fetch_xml(decrypt_value($feed['url']));
        $result = parse_vrsync_xml($xml);
        $listings = $result['listings'];
        $issues = $result['issues'];
        $totalFound = count($listings);
        $totalErrors = count($issues);

        $seenExternalIds = [];

        foreach ($listings as $listing) {
            $seenExternalIds[] = $listing['externalId'];
            try {
                [$cityId, $neighborhoodId] = vrsync_resolve_location($listing);

                $existingStmt = $pdo->prepare('SELECT id FROM properties WHERE origin = "VRSYNC" AND source_feed_id = ? AND external_code = ?');
                $existingStmt->execute([$feedId, $listing['externalId']]);
                $existingId = $existingStmt->fetchColumn();

                $status = $listing['active'] ? 'PUBLISHED' : 'PAUSED';
                $featuresJson = json_encode($listing['features'], JSON_UNESCAPED_UNICODE);

                if ($existingId) {
                    $upd = $pdo->prepare('UPDATE properties SET title=?, description=?, listing_type=?, property_type=?, price_sale=?, price_rent=?,
                        condo_fee=?, iptu=?, total_area=?, built_area=?, bedrooms=?, suites=?, bathrooms=?, parking_spaces=?, features=?,
                        status=?, city_id=?, neighborhood_id=?, street=?, number=?, zip_code=?, latitude=?, longitude=?, agency_id=? WHERE id=?');
                    $upd->execute([
                        $listing['title'], $listing['description'], $listing['listingType'], $listing['propertyType'],
                        $listing['priceSale'], $listing['priceRent'], $listing['condoFee'], $listing['iptu'],
                        $listing['totalArea'], $listing['builtArea'], $listing['bedrooms'], $listing['suites'],
                        $listing['bathrooms'], $listing['parkingSpaces'], $featuresJson, $status,
                        $cityId, $neighborhoodId, $listing['address']['street'], $listing['address']['number'],
                        $listing['address']['zipCode'], $listing['address']['latitude'], $listing['address']['longitude'],
                        $feed['agency_id'], $existingId,
                    ]);

                    $existingUrlsStmt = $pdo->prepare('SELECT url FROM property_images WHERE property_id = ?');
                    $existingUrlsStmt->execute([$existingId]);
                    $existingUrls = $existingUrlsStmt->fetchAll(PDO::FETCH_COLUMN);
                    $newPhotos = array_diff($listing['photos'], $existingUrls);
                    if ($newPhotos) {
                        $maxOrderStmt = $pdo->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = ?');
                        $maxOrderStmt->execute([$existingId]);
                        $order = (int) $maxOrderStmt->fetchColumn();
                        $insertImg = $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin) VALUES (?,?,?,?,"VRSYNC")');
                        foreach (array_values($newPhotos) as $idx => $url) {
                            $insertImg->execute([$existingId, $url, $order + $idx, ($order === 0 && $idx === 0) ? 1 : 0]);
                        }
                    }
                    $totalUpdated++;
                } else {
                    $advertiserId = vrsync_ensure_feed_advertiser((int) $feed['agency_id']);
                    $code = next_property_code($pdo);
                    $cityNameStmt = $pdo->prepare('SELECT name FROM cities WHERE id = ?');
                    $cityNameStmt->execute([$cityId]);
                    $cityName = $cityNameStmt->fetchColumn();
                    $slug = slugify($listing['title'] . ' ' . ($listing['address']['neighborhood'] ?? '') . ' ' . $cityName) . '-' . $listing['externalId'];

                    $ins = $pdo->prepare('INSERT INTO properties
                        (code, external_code, origin, source_feed_id, title, slug, description, listing_type, property_type,
                         price_sale, price_rent, condo_fee, iptu, total_area, built_area, bedrooms, suites, bathrooms, parking_spaces,
                         features, status, published_at, city_id, neighborhood_id, street, number, zip_code, latitude, longitude, advertiser_id, agency_id)
                        VALUES (?,?,"VRSYNC",?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,?,?,?,?,?,?,?,?,?)');
                    $ins->execute([
                        $code, $listing['externalId'], $feedId, $listing['title'], $slug, $listing['description'],
                        $listing['listingType'], $listing['propertyType'], $listing['priceSale'], $listing['priceRent'],
                        $listing['condoFee'], $listing['iptu'], $listing['totalArea'], $listing['builtArea'],
                        $listing['bedrooms'], $listing['suites'], $listing['bathrooms'], $listing['parkingSpaces'],
                        $featuresJson, $status, $cityId, $neighborhoodId, $listing['address']['street'],
                        $listing['address']['number'], $listing['address']['zipCode'], $listing['address']['latitude'],
                        $listing['address']['longitude'], $advertiserId, $feed['agency_id'],
                    ]);
                    $newId = (int) $pdo->lastInsertId();
                    $insertImg = $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin) VALUES (?,?,?,?,"VRSYNC")');
                    foreach ($listing['photos'] as $idx => $url) {
                        $insertImg->execute([$newId, $url, $idx, $idx === 0 ? 1 : 0]);
                    }
                    $totalCreated++;
                }
            } catch (\Throwable $e) {
                $totalErrors++;
                error_log('[vrsync] erro ao processar imóvel ' . $listing['externalId'] . ': ' . $e->getMessage());
            }
        }

        // Desativa imóveis do feed que não vieram nesta sincronização.
        if ($seenExternalIds) {
            $placeholders = implode(',', array_fill(0, count($seenExternalIds), '?'));
            $staleStmt = $pdo->prepare("SELECT id FROM properties WHERE origin = 'VRSYNC' AND source_feed_id = ? AND status != 'ARCHIVED' AND external_code NOT IN ($placeholders)");
            $staleStmt->execute([$feedId, ...$seenExternalIds]);
            $staleIds = $staleStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($staleIds) {
                $ph = implode(',', array_fill(0, count($staleIds), '?'));
                $pdo->prepare("UPDATE properties SET status = 'ARCHIVED', deactivated_at = CURRENT_TIMESTAMP WHERE id IN ($ph)")->execute($staleIds);
                $totalDeactivated = count($staleIds);
            }
        }

        $totalUnchanged = max(0, $totalFound - $totalCreated - $totalUpdated);
        $finalStatus = ($totalErrors > 0 && $totalCreated === 0 && $totalUpdated === 0) ? 'ERROR' : 'SUCCESS';
        $errorMessage = implode(' | ', array_map(fn($i) => $i['message'], array_slice($issues, 0, 5))) ?: null;

        $pdo->prepare('UPDATE feed_sync_logs SET finished_at = CURRENT_TIMESTAMP, status = ?, total_found = ?, total_created = ?, total_updated = ?, total_unchanged = ?, total_deactivated = ?, total_errors = ?, error_message = ? WHERE id = ?')
            ->execute([$finalStatus, $totalFound, $totalCreated, $totalUpdated, $totalUnchanged, $totalDeactivated, $totalErrors, $errorMessage, $logId]);

        $nextSyncAt = date('Y-m-d H:i:s', strtotime('+' . (int) $feed['frequency_minutes'] . ' minutes'));
        $pdo->prepare('UPDATE feeds SET last_sync_at = CURRENT_TIMESTAMP, next_sync_at = ?, last_run_status = ? WHERE id = ?')
            ->execute([$nextSyncAt, $finalStatus, $feedId]);
    } catch (\Throwable $e) {
        $pdo->prepare('UPDATE feed_sync_logs SET finished_at = CURRENT_TIMESTAMP, status = "ERROR", error_message = ?, total_errors = total_errors + 1 WHERE id = ?')
            ->execute([$e->getMessage(), $logId]);
        $pdo->prepare('UPDATE feeds SET last_sync_at = CURRENT_TIMESTAMP, last_run_status = "ERROR" WHERE id = ?')->execute([$feedId]);
    }

    $result = $pdo->prepare('SELECT * FROM feed_sync_logs WHERE id = ?');
    $result->execute([$logId]);
    return $result->fetch();
}
