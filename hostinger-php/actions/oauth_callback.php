<?php
require_once __DIR__ . '/../includes/bootstrap.php';

function oauth_fail(string $message): never
{
    $_SESSION['login_error'] = $message;
    redirect(base_url('login.php'));
}

$provider = $_GET['provider'] ?? '';
$expectedProvider = $_SESSION['oauth_provider'] ?? '';
$expectedState = $_SESSION['oauth_state'] ?? '';
$redirectTo = $_SESSION['oauth_redirect'] ?? '';
unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_redirect']);

if (!in_array($provider, OAUTH_PROVIDERS, true) || !oauth_configured($provider)) {
    oauth_fail('Provedor de login inválido.');
}

if (!empty($_GET['error'])) {
    // Usuário cancelou no provedor (ex.: "error=access_denied") — volta pro
    // login em silêncio, sem tratar como falha do sistema.
    redirect(base_url('login.php'));
}

rate_limit_enforce('oauth_callback_ip', client_ip(), 30, 600);
rate_limit_hit('oauth_callback_ip', client_ip());

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
if ($provider !== $expectedProvider || !$state || !hash_equals($expectedState, $state) || !$code) {
    oauth_fail('Sessão de login expirada, tente novamente.');
}

try {
    $accessToken = oauth_exchange_code($provider, $code);
    $profile = oauth_fetch_profile($provider, $accessToken);
    $userId = oauth_login_or_register($provider, $profile);
    login_user($userId);
} catch (OAuthError $e) {
    oauth_fail($e->getMessage());
}

redirect($redirectTo ?: base_url('minha-conta.php'));
