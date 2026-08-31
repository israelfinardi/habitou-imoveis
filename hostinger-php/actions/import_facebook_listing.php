<?php
require_once __DIR__ . '/../includes/bootstrap.php';
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

// Cada tentativa dispara uma requisição de saída do nosso servidor pra um
// link escolhido pelo usuário — limita pra evitar abuso desse endpoint como
// proxy de requisições.
rate_limit_enforce('facebook_import', (string) $user['id'], 15, 3600);

$url = trim((string) ($body['url'] ?? ''));
if ($url === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Cole o link do anúncio do Facebook Marketplace.']);
    exit;
}

try {
    rate_limit_hit('facebook_import', (string) $user['id']);
    $result = facebook_import_scrape_listing($url);
    echo json_encode(array_merge(['ok' => true], $result));
} catch (FacebookImportError $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Não foi possível importar esse anúncio agora. Preencha os campos manualmente.']);
}
