<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('habitou_session');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    // HttpOnly bloqueia leitura do cookie por JS (mitiga roubo de sessão via
    // XSS); SameSite=Strict impede que ele seja enviado em navegação vinda
    // de outro site (mitiga CSRF, complementar ao token já usado nos
    // formulários); Secure só quando o acesso já é HTTPS — exigir sempre
    // quebraria o login em ambiente de desenvolvimento local sem HTTPS.
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
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

/**
 * Quem pode gerir um contrato (editar, ativar/desativar, redigir a partir de
 * um modelo): admin, o anunciante do imóvel original, ou alguém da mesma
 * imobiliária — mesma regra de can_manage_property(), já que um contrato
 * nasce sempre a partir de um imóvel e herda seus responsáveis.
 */
function can_manage_contract(array $user, array $contract): bool
{
    if ($user['role'] === 'ADMIN') {
        return true;
    }
    if ((int) ($contract['advertiser_id'] ?? 0) === (int) $user['id']) {
        return true;
    }
    if (!empty($contract['agency_id']) && (int) ($user['agency_id'] ?? 0) === (int) $contract['agency_id'] && in_array($user['role'], AGENCY_ROLES, true)) {
        return true;
    }
    return false;
}

/**
 * Quem pode VER um contrato — mais amplo que can_manage_contract(): inclui
 * o cliente (buyer_id/tenant_id) e o corretor individual (agent_id), que
 * têm acesso de leitura (baixar PDF, enviar documento assinado) mas não
 * podem editar o contrato nem mudar seu status.
 */
function can_view_contract(array $user, array $contract): bool
{
    if (can_manage_contract($user, $contract)) {
        return true;
    }
    $partyIds = [$contract['owner_id'] ?? null, $contract['buyer_id'] ?? null, $contract['tenant_id'] ?? null, $contract['agent_id'] ?? null];
    return in_array((int) $user['id'], array_map('intval', array_filter($partyIds)), true);
}

/** Mesma regra de can_manage_contract(), aplicada a um modelo de contrato. */
function can_manage_contract_template(array $user, array $template): bool
{
    if ($user['role'] === 'ADMIN') {
        return true;
    }
    if ((int) $template['created_by'] === (int) $user['id']) {
        return true;
    }
    if (!empty($template['agency_id']) && (int) ($user['agency_id'] ?? 0) === (int) $template['agency_id'] && in_array($user['role'], AGENCY_ROLES, true)) {
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
