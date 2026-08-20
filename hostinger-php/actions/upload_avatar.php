<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/avatar.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}
verify_csrf();

$file = $_FILES['avatar'] ?? null;
if (!$file) {
    http_response_code(400);
    echo json_encode(['error' => 'Erro ao enviar arquivo.']);
    exit;
}

try {
    $url = handle_avatar_upload((int) $user['id'], $file);
    echo json_encode(['url' => $url]);
} catch (AvatarUploadError $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
