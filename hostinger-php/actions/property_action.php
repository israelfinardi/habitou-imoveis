<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$user = require_login();
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['do'] ?? '';

try {
    switch ($action) {
        case 'publish':
        case 'pause':
        case 'archive':
        case 'reactivate':
        case 'renew':
            set_property_status($id, $action, $user);
            redirect(base_url('anunciante/imoveis.php'));
            break;
        case 'duplicate':
            $newId = duplicate_property($id, $user);
            redirect(base_url('anunciante/editar.php?id=' . $newId));
            break;
        case 'delete':
            delete_property($id, $user);
            redirect(base_url('anunciante/imoveis.php'));
            break;
        default:
            redirect(base_url('anunciante/imoveis.php'));
    }
} catch (\Throwable $e) {
    http_response_code(400);
    echo e($e->getMessage());
}
