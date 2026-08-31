<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
require_once __DIR__ . '/../includes/facebook_import.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json']);
    exit;
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf']);
    exit;
}

$propertyId = (int) ($body['property_id'] ?? 0);
$property = get_property_by_id($propertyId);
if (!$property || !can_manage_property($user, $property)) {
    http_response_code(403);
    echo json_encode(['error' => 'Você não pode editar fotos deste imóvel.']);
    exit;
}

$urls = is_array($body['urls'] ?? null) ? array_slice($body['urls'], 0, 10) : [];
rate_limit_enforce('facebook_import_photos', (string) $user['id'], 30, 3600);
rate_limit_hit('facebook_import_photos', (string) $user['id']);

$allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$uploaded = [];
$errors = [];

foreach ($urls as $url) {
    if (!is_string($url) || $url === '') {
        continue;
    }
    try {
        [$tmpPath, $mime] = facebook_import_download_photo($url);
        if (!isset($allowedMime[$mime])) {
            @unlink($tmpPath);
            $errors[] = 'Uma das fotos do anúncio não está num formato suportado.';
            continue;
        }

        $dir = __DIR__ . '/../uploads/properties/' . $propertyId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(12)) . '.' . $allowedMime[$mime];
        $destination = $dir . '/' . $filename;
        if (!rename($tmpPath, $destination)) {
            @unlink($tmpPath);
            $errors[] = 'Falha ao salvar uma das fotos importadas.';
            continue;
        }

        $imageUrl = base_url('uploads/properties/' . $propertyId . '/' . $filename);
        $imageId = add_property_image($propertyId, $imageUrl, $user, filesize($destination));
        $uploaded[] = ['id' => $imageId, 'url' => $imageUrl];
    } catch (FacebookImportError $e) {
        $errors[] = $e->getMessage();
    }
}

echo json_encode(['images' => $uploaded, 'errors' => $errors]);
