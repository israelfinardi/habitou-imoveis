<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
verify_csrf();

$action = $_POST['do'] ?? '';
$pdo = db();

if ($action === 'subscribe') {
    $planId = (int) $_POST['plan_id'];
    $stmt = $pdo->prepare('INSERT INTO subscriptions (user_id, agency_id, plan_id, status) VALUES (?,?,?,"PENDING")');
    $stmt->execute([$user['id'], $user['agency_id'] ?: null, $planId]);
} elseif ($action === 'cancel') {
    $subId = (int) $_POST['subscription_id'];
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE id = ?');
    $stmt->execute([$subId]);
    $sub = $stmt->fetch();
    if ($sub && ($sub['user_id'] == $user['id'] || (!empty($sub['agency_id']) && $sub['agency_id'] == ($user['agency_id'] ?? null)) || $user['role'] === 'ADMIN')) {
        $pdo->prepare('UPDATE subscriptions SET status = "CANCELED", canceled_at = NOW() WHERE id = ?')->execute([$subId]);
    }
}

redirect(base_url('planos.php'));
