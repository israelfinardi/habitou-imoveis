<?php
require_once __DIR__ . '/xml_import_parser.php';
require_once __DIR__ . '/property_mutations.php';

const XML_IMPORT_STATUS_LABEL = ['PROCESSING' => 'Processando', 'SUCCESS' => 'Concluído', 'PARTIAL' => 'Concluído com avisos', 'ERROR' => 'Erro'];
const XML_IMPORT_MAX_IMAGE_BYTES = 8 * 1024 * 1024;
const XML_IMPORT_MAX_PHOTOS_PER_PROPERTY = 20;
const XML_IMPORT_MAX_FILE_BYTES = 20 * 1024 * 1024;

function list_xml_imports_for_user(array $actor): array
{
    $pdo = db();
    if ($actor['role'] === 'ADMIN') {
        $stmt = $pdo->query('SELECT i.*, u.first_name, u.last_name, u.email AS user_email FROM xml_imports i JOIN users u ON u.id = i.user_id ORDER BY i.created_at DESC');
        return $stmt->fetchAll();
    }
    $stmt = $pdo->prepare('SELECT i.*, u.first_name, u.last_name, u.email AS user_email FROM xml_imports i JOIN users u ON u.id = i.user_id
        WHERE i.user_id = :uid OR i.agency_id = :aid ORDER BY i.created_at DESC');
    $stmt->execute(['uid' => $actor['id'], 'aid' => $actor['agency_id'] ?? 0]);
    return $stmt->fetchAll();
}

function get_xml_import(int $id, array $actor): ?array
{
    $stmt = db()->prepare('SELECT i.*, u.first_name, u.last_name, u.email AS user_email FROM xml_imports i JOIN users u ON u.id = i.user_id WHERE i.id = ?');
    $stmt->execute([$id]);
    $import = $stmt->fetch();
    if (!$import) {
        return null;
    }
    $authorized = $actor['role'] === 'ADMIN' || (int) $import['user_id'] === (int) $actor['id']
        || (!empty($import['agency_id']) && $import['agency_id'] == ($actor['agency_id'] ?? null));
    return $authorized ? $import : null;
}

/** "Santa Catarina" ou "SC" -> "SC" — alguns feeds mandam o nome do estado por extenso em vez da UF. */
function xml_import_resolve_state_code(string $raw): ?string
{
    $raw = trim($raw);
    $upper = mb_strtoupper($raw);
    if (mb_strlen($upper) === 2 && isset(BRAZIL_STATES[$upper])) {
        return $upper;
    }
    foreach (BRAZIL_STATES as $code => $name) {
        if (mb_strtolower($name) === mb_strtolower($raw)) {
            return $code;
        }
    }
    return mb_strlen($upper) >= 2 ? mb_substr($upper, 0, 2) : null;
}

function xml_import_resolve_location(array $listing): array
{
    $stateCode = xml_import_resolve_state_code($listing['address']['state']);
    if (!$stateCode) {
        throw new \RuntimeException('Estado não reconhecido: ' . $listing['address']['state']);
    }
    $cityId = get_or_create_city($listing['address']['city'] . ' (' . $stateCode . ')');
    if (!$cityId) {
        throw new \RuntimeException('Não foi possível resolver a cidade: ' . $listing['address']['city'] . '/' . $stateCode);
    }
    $neighborhoodId = null;
    if (!empty($listing['address']['neighborhood'])) {
        $neighborhoodId = get_or_create_neighborhood($cityId, $listing['address']['neighborhood']);
    }
    return [$cityId, $neighborhoodId];
}

/**
 * Baixa uma foto (URL externa vinda do XML) e salva localmente em
 * uploads/properties/{id}/ — mesma convenção de nome de arquivo aleatório
 * usada no upload manual (actions/upload_foto.php), pra continuar servida
 * do próprio site em vez de depender do host de origem ficar no ar.
 * Falha em uma foto isolada não derruba o import inteiro: devolve null e
 * quem chamou simplesmente pula essa foto.
 */
function xml_import_download_photo(string $url, int $propertyId): ?string
{
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'header' => "User-Agent: HabitouImoveisImporter/1.0\r\n", 'follow_location' => 1, 'max_redirects' => 3],
        'https' => ['timeout' => 10, 'header' => "User-Agent: HabitouImoveisImporter/1.0\r\n", 'follow_location' => 1, 'max_redirects' => 3],
    ]);
    $data = @file_get_contents($url, false, $context, 0, XML_IMPORT_MAX_IMAGE_BYTES + 1);
    if ($data === false || strlen($data) === 0 || strlen($data) > XML_IMPORT_MAX_IMAGE_BYTES) {
        return null;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'ximg');
    file_put_contents($tmpFile, $data);
    $mime = @mime_content_type($tmpFile);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        @unlink($tmpFile);
        return null;
    }

    $dir = __DIR__ . '/../uploads/properties/' . $propertyId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!rename($tmpFile, $dir . '/' . $filename)) {
        @unlink($tmpFile);
        return null;
    }

    return base_url('uploads/properties/' . $propertyId . '/' . $filename);
}

/**
 * Cria o registro de importação (status PROCESSING) e devolve o id — a
 * página de upload chama isso e, em seguida, process_xml_import() logo
 * depois, tudo na mesma requisição (sem fila/cron: mais simples e
 * consistente com o resto do site, que já é PHP puro sem worker separado).
 */
function create_xml_import(array $actor, string $originalFilename, string $storedPath): int
{
    $pdo = db();
    $pdo->prepare('INSERT INTO xml_imports (user_id, agency_id, original_filename, stored_path, status) VALUES (?,?,?,?,"PROCESSING")')
        ->execute([$actor['id'], $actor['agency_id'] ?? null, $originalFilename, $storedPath]);
    return (int) $pdo->lastInsertId();
}

/**
 * Processa o XML já salvo em disco (ver create_xml_import()): parseia,
 * resolve cidade/bairro, cria ou atualiza cada imóvel (casado pelo par
 * origin=XML_IMPORT + external_code, escopado ao mesmo anunciante/agência
 * que fez o upload — assim reimportar o mesmo arquivo atualiza os imóveis
 * já existentes em vez de duplicar) e baixa as fotos localmente.
 */
function process_xml_import(int $importId, array $actor): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM xml_imports WHERE id = ?');
    $stmt->execute([$importId]);
    $import = $stmt->fetch();
    if (!$import) {
        throw new \RuntimeException('Importação não encontrada.');
    }

    $totalFound = 0; $totalCreated = 0; $totalUpdated = 0; $totalDeactivated = 0; $totalErrors = 0;

    try {
        $xml = file_get_contents($import['stored_path']);
        if ($xml === false) {
            throw new \RuntimeException('Não foi possível ler o arquivo XML enviado.');
        }
        $result = parse_property_xml($xml);
        $listings = $result['listings'];
        $issues = $result['issues'];
        $totalFound = count($listings);
        $totalErrors = count($issues);

        $agencyId = $actor['agency_id'] ?? null;
        $seenExternalIds = [];

        foreach ($listings as $listing) {
            try {
                $seenExternalIds[] = $listing['externalId'];
                [$cityId, $neighborhoodId] = xml_import_resolve_location($listing);

                $existingStmt = $pdo->prepare('SELECT id FROM properties WHERE origin = "XML_IMPORT" AND external_code = ? AND advertiser_id = ?');
                $existingStmt->execute([$listing['externalId'], $actor['id']]);
                $existingId = $existingStmt->fetchColumn();

                $status = $listing['active'] ? 'PUBLISHED' : 'PAUSED';
                $featuresJson = json_encode($listing['features'], JSON_UNESCAPED_UNICODE);

                if ($existingId) {
                    $upd = $pdo->prepare('UPDATE properties SET title=?, description=?, listing_type=?, property_type=?, price_sale=?, price_rent=?,
                        condo_fee=?, iptu=?, total_area=?, built_area=?, bedrooms=?, suites=?, bathrooms=?, parking_spaces=?, features=?,
                        status=?, city_id=?, neighborhood_id=?, street=?, number=?, zip_code=?, latitude=?, longitude=?, import_id=?, updated_at=CURRENT_TIMESTAMP
                        WHERE id=?');
                    $upd->execute([
                        $listing['title'], $listing['description'], $listing['listingType'], $listing['propertyType'],
                        $listing['priceSale'], $listing['priceRent'], $listing['condoFee'], $listing['iptu'],
                        $listing['totalArea'], $listing['builtArea'], $listing['bedrooms'], $listing['suites'],
                        $listing['bathrooms'], $listing['parkingSpaces'], $featuresJson, $status,
                        $cityId, $neighborhoodId, $listing['address']['street'], $listing['address']['number'],
                        $listing['address']['zipCode'], $listing['address']['latitude'], $listing['address']['longitude'],
                        $importId, $existingId,
                    ]);
                    $propertyId = (int) $existingId;

                    $existingUrlCountStmt = $pdo->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = ?');
                    $existingUrlCountStmt->execute([$propertyId]);
                    $existingPhotoCount = (int) $existingUrlCountStmt->fetchColumn();
                    $totalUpdated++;
                } else {
                    $code = next_property_code($pdo);
                    $cityNameStmt = $pdo->prepare('SELECT name FROM cities WHERE id = ?');
                    $cityNameStmt->execute([$cityId]);
                    $cityName = $cityNameStmt->fetchColumn();
                    $slug = slugify($listing['title'] . ' ' . ($listing['address']['neighborhood'] ?? '') . ' ' . $cityName) . '-' . slugify($listing['externalId']);

                    $ins = $pdo->prepare('INSERT INTO properties
                        (code, external_code, origin, import_id, title, slug, description, listing_type, property_type,
                         price_sale, price_rent, condo_fee, iptu, total_area, built_area, bedrooms, suites, bathrooms, parking_spaces,
                         features, status, published_at, city_id, neighborhood_id, street, number, zip_code, latitude, longitude, advertiser_id, agency_id)
                        VALUES (?,?,"XML_IMPORT",?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,?,?,?,?,?,?,?,?,?)');
                    $ins->execute([
                        $code, $listing['externalId'], $importId, $listing['title'], $slug, $listing['description'],
                        $listing['listingType'], $listing['propertyType'], $listing['priceSale'], $listing['priceRent'],
                        $listing['condoFee'], $listing['iptu'], $listing['totalArea'], $listing['builtArea'],
                        $listing['bedrooms'], $listing['suites'], $listing['bathrooms'], $listing['parkingSpaces'],
                        $featuresJson, $status, $cityId, $neighborhoodId, $listing['address']['street'],
                        $listing['address']['number'], $listing['address']['zipCode'], $listing['address']['latitude'],
                        $listing['address']['longitude'], $actor['id'], $agencyId,
                    ]);
                    $propertyId = (int) $pdo->lastInsertId();
                    $existingPhotoCount = 0;
                    $totalCreated++;
                }

                // Baixa só fotos que ainda não temos (evita rebaixar tudo de
                // novo a cada reimportação do mesmo arquivo).
                if ($existingPhotoCount < XML_IMPORT_MAX_PHOTOS_PER_PROPERTY) {
                    $slots = XML_IMPORT_MAX_PHOTOS_PER_PROPERTY - $existingPhotoCount;
                    $insertImg = $pdo->prepare('INSERT INTO property_images (property_id, url, `order`, is_primary, origin) VALUES (?,?,?,?,"XML_IMPORT")');
                    $order = $existingPhotoCount;
                    foreach (array_slice($listing['photos'], 0, $slots) as $photoUrl) {
                        $localUrl = xml_import_download_photo($photoUrl, $propertyId);
                        if ($localUrl) {
                            $insertImg->execute([$propertyId, $localUrl, $order, ($order === 0) ? 1 : 0]);
                            $order++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $totalErrors++;
                error_log('[xml_import] erro ao processar imóvel ' . ($listing['externalId'] ?? '?') . ': ' . $e->getMessage());
            }
        }

        // Imóveis que esse anunciante já tinha importado por XML antes, mas
        // que não vieram nesta importação — considerados removidos do
        // catálogo de origem, arquivados em vez de deixados publicados
        // desatualizados. Cada arquivo XML é tratado como o catálogo
        // completo e atual do anunciante, igual a uma sincronização.
        if ($seenExternalIds) {
            $placeholders = implode(',', array_fill(0, count($seenExternalIds), '?'));
            $staleStmt = $pdo->prepare("SELECT id FROM properties WHERE origin = 'XML_IMPORT' AND advertiser_id = ? AND status != 'ARCHIVED' AND external_code NOT IN ($placeholders)");
            $staleStmt->execute([$actor['id'], ...$seenExternalIds]);
            $staleIds = $staleStmt->fetchAll(PDO::FETCH_COLUMN);
            if ($staleIds) {
                $ph = implode(',', array_fill(0, count($staleIds), '?'));
                $pdo->prepare("UPDATE properties SET status = 'ARCHIVED', deactivated_at = CURRENT_TIMESTAMP WHERE id IN ($ph)")->execute($staleIds);
                $totalDeactivated = count($staleIds);
            }
        }

        $finalStatus = $totalErrors > 0 ? ($totalCreated + $totalUpdated > 0 ? 'PARTIAL' : 'ERROR') : 'SUCCESS';
        $errorSummary = implode(' | ', array_map(fn ($i) => $i['message'], array_slice($issues, 0, 5))) ?: null;

        $pdo->prepare('UPDATE xml_imports SET status=?, total_found=?, total_created=?, total_updated=?, total_deactivated=?, total_errors=?, error_summary=?, finished_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$finalStatus, $totalFound, $totalCreated, $totalUpdated, $totalDeactivated, $totalErrors, $errorSummary, $importId]);
    } catch (\Throwable $e) {
        $pdo->prepare('UPDATE xml_imports SET status="ERROR", error_summary=?, finished_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$e->getMessage(), $importId]);
    }

    $stmt = $pdo->prepare('SELECT * FROM xml_imports WHERE id = ?');
    $stmt->execute([$importId]);
    return $stmt->fetch();
}
