<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';

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

    case 'toggle_featured':
        $id = (int) $_POST['id'];
        $featured = !empty($_POST['featured']);
        if ($featured) {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM properties WHERE is_featured = 1')->fetchColumn();
            if ($count >= FEATURED_PROPERTIES_LIMIT) {
                $_SESSION['admin_error'] = 'Já existem ' . FEATURED_PROPERTIES_LIMIT . ' imóveis em destaque — remova um antes de adicionar outro.';
                redirect(base_url('admin/imoveis.php'));
            }
        }
        $pdo->prepare('UPDATE properties SET is_featured = ? WHERE id = ?')->execute([$featured ? 1 : 0, $id]);
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

    case 'delete_plan':
        $id = (int) $_POST['id'];
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE plan_id = ?');
        $countStmt->execute([$id]);
        if ((int) $countStmt->fetchColumn() > 0) {
            $_SESSION['admin_error'] = 'Não é possível excluir: existem assinaturas (ativas ou passadas) vinculadas a este plano. Desative-o em vez de excluir.';
        } else {
            $pdo->prepare('DELETE FROM plans WHERE id = ?')->execute([$id]);
            $_SESSION['admin_success'] = 'Plano excluído.';
        }
        redirect(base_url('admin/planos.php'));
        break;

    case 'publish_plan_mp':
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ?');
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        if (!$plan) {
            redirect(base_url('admin/planos.php'));
        }
        if ($plan['mp_plan_id']) {
            $_SESSION['admin_error'] = 'Este plano já está publicado no Mercado Pago.';
        } else {
            try {
                publish_plan_to_mercadopago($plan);
                $_SESSION['admin_success'] = 'Plano "' . $plan['name'] . '" publicado no Mercado Pago.';
            } catch (\Throwable $e) {
                $_SESSION['admin_error'] = 'Falha ao publicar no Mercado Pago: ' . $e->getMessage();
            }
        }
        redirect(base_url('admin/planos.php'));
        break;

    case 'sync_mp_plans':
        try {
            $result = sync_plans_from_mercadopago();
            $_SESSION['admin_success'] = "Sincronizado: {$result['total']} plano(s) no Mercado Pago — {$result['created']} novo(s), {$result['updated']} atualizado(s).";
        } catch (\Throwable $e) {
            $_SESSION['admin_error'] = 'Falha ao sincronizar com o Mercado Pago: ' . $e->getMessage();
        }
        redirect(base_url('admin/planos.php'));
        break;

    case 'update_subscription_status':
        $id = (int) $_POST['id'];
        $status = $_POST['status'];
        if ($status === 'ACTIVE') {
            $pdo->prepare('UPDATE subscriptions SET status = ?, started_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$status, $id]);
        } elseif ($status === 'CANCELED') {
            $pdo->prepare('UPDATE subscriptions SET status = ?, canceled_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$status, $id]);
        } else {
            $pdo->prepare('UPDATE subscriptions SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
        redirect(base_url('admin/assinaturas.php'));
        break;

    case 'create_poi':
        $name = trim($_POST['name'] ?? '');
        $type = array_key_exists($_POST['type'] ?? '', POI_TYPE_LABEL) ? $_POST['type'] : 'OUTRO';
        $lat = filter_var($_POST['latitude'] ?? '', FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['longitude'] ?? '', FILTER_VALIDATE_FLOAT);
        $cityId = get_or_create_city(trim($_POST['cityLabel'] ?? ''));
        if ($name === '' || $lat === false || $lng === false || !$cityId) {
            $_SESSION['admin_error'] = 'Preencha nome, cidade e coordenadas válidas.';
        } else {
            $pdo->prepare('INSERT INTO points_of_interest (name, type, city_id, latitude, longitude) VALUES (?,?,?,?,?)')
                ->execute([$name, $type, $cityId, $lat, $lng]);
            $_SESSION['admin_success'] = 'Ponto de interesse adicionado.';
        }
        redirect(base_url('admin/pontos-de-interesse.php'));
        break;

    case 'delete_poi':
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM points_of_interest WHERE id = ?')->execute([$id]);
        redirect(base_url('admin/pontos-de-interesse.php'));
        break;

    default:
        redirect(base_url('admin/index.php'));
}
