<?php
/**
 * Desafio invisível anti-bot (Cloudflare Turnstile) no login e cadastro —
 * só ativa quando TURNSTILE_SITE_KEY/TURNSTILE_SECRET_KEY estão
 * preenchidas em config/config.php; em branco, captcha_configured()
 * devolve false e nada muda (sem widget, sem verificação).
 */

function captcha_configured(): bool
{
    return defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''
        && defined('TURNSTILE_SECRET_KEY') && TURNSTILE_SECRET_KEY !== '';
}

function render_captcha_widget(): string
{
    if (!captcha_configured()) {
        return '';
    }
    return '<div class="cf-turnstile mb-4" data-sitekey="' . e(TURNSTILE_SITE_KEY) . '"></div>'
        . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
}

/** Sempre chame antes de qualquer ação sensível (login, cadastro). */
function verify_captcha(): bool
{
    if (!captcha_configured()) {
        return true;
    }
    $token = $_POST['cf-turnstile-response'] ?? '';
    if (!$token) {
        return false;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => TURNSTILE_SECRET_KEY,
            'response' => $token,
            'remoteip' => client_ip(),
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $result = json_decode((string) $raw, true);
    return !empty($result['success']);
}
