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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['do'] ?? '';

try {
    switch ($action) {
        case 'remove':
            remove_property_image((int) $input['image_id'], $user);
            break;
        case 'primary':
            set_primary_image((int) $input['image_id'], $user);
            break;
        case 'reorder':
            $pdo = db();
            foreach ($input['ordered_ids'] ?? [] as $order => $imageId) {
                $stmt = $pdo->prepare('SELECT pi.*, p.advertiser_id, p.agency_id FROM property_images pi JOIN properties p ON p.id = pi.property_id WHERE pi.id = ?');
                $stmt->execute([(int) $imageId]);
                $image = $stmt->fetch();
                if ($image && can_manage_property($user, $image)) {
                    $pdo->prepare('UPDATE property_images SET `order` = ? WHERE id = ?')->execute([$order, (int) $imageId]);
                }
            }
            break;
    }
    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
