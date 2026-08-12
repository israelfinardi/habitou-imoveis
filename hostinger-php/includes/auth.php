<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('habitou_session');
    session_start();
}

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND status = "ACTIVE"');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    unset($row['password_hash']);
    $user = $row;
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect(base_url('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '')));
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
    return $user;
}

const AGENCY_ROLES = ['AGENCY_ADMIN', 'AGENT'];

function can_manage_property(array $user, array $property): bool
{
    if ($user['role'] === 'ADMIN') {
        return true;
    }
    if ((int) $property['advertiser_id'] === (int) $user['id']) {
        return true;
    }
    if (!empty($property['agency_id']) && (int) $user['agency_id'] === (int) $property['agency_id'] && in_array($user['role'], AGENCY_ROLES, true)) {
        return true;
    }
    return false;
}

/** Token CSRF simples por sessão. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = random_token(16);
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo 'Sessão inválida, recarregue a página e tente novamente.';
        exit;
    }
}
