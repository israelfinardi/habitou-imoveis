<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_role(['ADMIN']);
verify_csrf();

$pdo = db();
$action = $_POST['do'] ?? '';

switch ($action) {
    case 'update_user':
        $id = (int) $_POST['id'];
        $role = $_POST['role'];
        $status = $_POST['status'];
        $pdo->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?')->execute([$role, $status, $id]);
        redirect(base_url('admin/usuarios.php'));
        break;

    case 'update_property_status':
        $id = (int) $_POST['id'];
        $status = $_POST['status'];
        $pdo->prepare('UPDATE properties SET status = ? WHERE id = ?')->execute([$status, $id]);
        redirect(base_url('admin/imoveis.php'));
        break;

    case 'delete_property':
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM properties WHERE id = ?')->execute([$id]);
        redirect(base_url('admin/imoveis.php'));
        break;

    case 'update_agency_status':
        $id = (int) $_POST['id'];
        $status = $_POST['status'];
        $pdo->prepare('UPDATE agencies SET status = ? WHERE id = ?')->execute([$status, $id]);
        redirect(base_url('admin/imobiliarias.php'));
        break;

    case 'create_plan':
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $maxListings = $_POST['maxListings'] !== '' ? (int) $_POST['maxListings'] : null;
        $features = array_values(array_filter(array_map('trim', explode("\n", $_POST['features'] ?? ''))));
        if ($name) {
            $pdo->prepare('INSERT INTO plans (name, slug, description, price, max_listings, features) VALUES (?,?,?,?,?,?)')
                ->execute([$name, slugify($name), $description, $price, $maxListings, json_encode($features, JSON_UNESCAPED_UNICODE)]);
        }
        redirect(base_url('admin/planos.php'));
        break;

    case 'toggle_plan':
        $id = (int) $_POST['id'];
        $active = (int) $_POST['active'];
        $pdo->prepare('UPDATE plans SET active = ? WHERE id = ?')->execute([$active, $id]);
        redirect(base_url('admin/planos.php'));
        break;

    case 'update_subscription_status':
        $id = (int) $_POST['id'];
        $status = $_POST['status'];
        if ($status === 'ACTIVE') {
            $pdo->prepare('UPDATE subscriptions SET status = ?, started_at = NOW() WHERE id = ?')->execute([$status, $id]);
        } elseif ($status === 'CANCELED') {
            $pdo->prepare('UPDATE subscriptions SET status = ?, canceled_at = NOW() WHERE id = ?')->execute([$status, $id]);
        } else {
            $pdo->prepare('UPDATE subscriptions SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
        redirect(base_url('admin/assinaturas.php'));
        break;

    default:
        redirect(base_url('admin/index.php'));
}
