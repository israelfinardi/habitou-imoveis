<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}
verify_csrf();

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$file = $_FILES['avatar'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Erro ao enviar arquivo.']);
    exit;
}
$type = mime_content_type($file['tmp_name']) ?: $file['type'];
if (!isset($allowed[$type])) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato não suportado. Envie JPG, PNG ou WEBP.']);
    exit;
}
if ($file['size'] > UPLOAD_MAX_BYTES) {
    http_response_code(400);
    echo json_encode(['error' => 'Arquivo muito grande (máx. 8MB).']);
    exit;
}

$dir = __DIR__ . '/../uploads/avatars/' . $user['id'];
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$filename = bin2hex(random_bytes(12)) . '.' . $allowed[$type];
$destination = $dir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao salvar o arquivo.']);
    exit;
}

$url = base_url('uploads/avatars/' . $user['id'] . '/' . $filename);
db()->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')->execute([$url, $user['id']]);

echo json_encode(['url' => $url]);
