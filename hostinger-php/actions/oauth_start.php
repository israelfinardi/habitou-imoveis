<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$provider = $_GET['provider'] ?? '';
if (!in_array($provider, OAUTH_PROVIDERS, true) || !oauth_configured($provider)) {
    redirect(base_url('login.php'));
}

rate_limit_enforce('oauth_start_ip', client_ip(), 30, 600);
rate_limit_hit('oauth_start_ip', client_ip());

$redirect = $_GET['redirect'] ?? '';
$state = random_token(16);
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = $provider;
$_SESSION['oauth_redirect'] = ($redirect && str_starts_with($redirect, '/')) ? $redirect : '';

redirect(oauth_authorize_url($provider, $state));
