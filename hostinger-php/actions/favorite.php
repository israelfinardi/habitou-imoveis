<?php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$propertyId = (int) ($_POST['property_id'] ?? 0);
if (!$propertyId) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_property']);
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND property_id = ?');
$stmt->execute([$user['id'], $propertyId]);
$existing = $stmt->fetchColumn();

if ($existing) {
    $pdo->prepare('DELETE FROM favorites WHERE id = ?')->execute([$existing]);
    echo json_encode(['favorite' => false]);
} else {
    $pdo->prepare('INSERT INTO favorites (user_id, property_id) VALUES (?, ?)')->execute([$user['id'], $propertyId]);
    echo json_encode(['favorite' => true]);
}
