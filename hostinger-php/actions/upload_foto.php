<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$propertyId = (int) ($_POST['property_id'] ?? 0);
$property = get_property_by_id($propertyId);
if (!$property || !can_manage_property($user, $property)) {
    http_response_code(403);
    echo json_encode(['error' => 'Você não pode editar fotos deste imóvel.']);
    exit;
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$uploaded = [];
$errors = [];

foreach ($_FILES['files']['tmp_name'] ?? [] as $idx => $tmpName) {
    $error = $_FILES['files']['error'][$idx];
    $size = $_FILES['files']['size'][$idx];
    $type = mime_content_type($tmpName) ?: $_FILES['files']['type'][$idx];

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro ao enviar arquivo.';
        continue;
    }
    if (!isset($allowed[$type])) {
        $errors[] = 'Formato não suportado. Envie JPG, PNG ou WEBP.';
        continue;
    }
    if ($size > UPLOAD_MAX_BYTES) {
        $errors[] = 'Arquivo muito grande (máx. 8MB).';
        continue;
    }

    $dir = __DIR__ . '/../uploads/properties/' . $propertyId;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$type];
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        $errors[] = 'Falha ao salvar o arquivo.';
        continue;
    }

    $url = base_url('uploads/properties/' . $propertyId . '/' . $filename);
    $imageId = add_property_image($propertyId, $url, $user, $size);
    $uploaded[] = ['id' => $imageId, 'url' => $url];
}

echo json_encode(['images' => $uploaded, 'errors' => $errors]);
