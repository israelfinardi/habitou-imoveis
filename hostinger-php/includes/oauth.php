<?php
/**
 * Login social (Google + Facebook), OAuth 2.0 padrão — "Authorization Code"
 * — só ativa quando as credenciais em config/config.php estão preenchidas
 * (mesmo padrão "vazio = desligado" do Turnstile em includes/captcha.php).
 *
 * Fluxo: actions/oauth_start.php (redireciona pro provedor) ->
 * actions/oauth_callback.php (troca o code por token, busca o perfil e
 * chama oauth_login_or_register() + login_user()).
 *
 * Isso é login OAuth padrão (o mesmo "Entrar com Google/Facebook" usado em
 * qualquer site) — não tem relação com raspagem de dados do Facebook
 * Marketplace, que foi avaliada e descartada anteriormente neste projeto.
 */

require_once __DIR__ . '/password.php';

class OAuthError extends \RuntimeException {}

const OAUTH_PROVIDERS = ['google', 'facebook'];

function oauth_provider_label(string $provider): string
{
    return $provider === 'google' ? 'Google' : 'Facebook';
}

function oauth_configured(string $provider): bool
{
    if ($provider === 'google') {
        return defined('GOOGLE_OAUTH_CLIENT_ID') && GOOGLE_OAUTH_CLIENT_ID !== ''
            && defined('GOOGLE_OAUTH_CLIENT_SECRET') && GOOGLE_OAUTH_CLIENT_SECRET !== '';
    }
    if ($provider === 'facebook') {
        return defined('FACEBOOK_OAUTH_APP_ID') && FACEBOOK_OAUTH_APP_ID !== ''
            && defined('FACEBOOK_OAUTH_APP_SECRET') && FACEBOOK_OAUTH_APP_SECRET !== '';
    }
    return false;
}

function oauth_any_configured(): bool
{
    foreach (OAUTH_PROVIDERS as $provider) {
        if (oauth_configured($provider)) {
            return true;
        }
    }
    return false;
}

function oauth_redirect_uri(string $provider): string
{
    return base_url('actions/oauth_callback.php?provider=' . $provider);
}

function oauth_authorize_url(string $provider, string $state): string
{
    if ($provider === 'google') {
        $params = [
            'client_id' => GOOGLE_OAUTH_CLIENT_ID,
            'redirect_uri' => oauth_redirect_uri('google'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    if ($provider === 'facebook') {
        $params = [
            'client_id' => FACEBOOK_OAUTH_APP_ID,
            'redirect_uri' => oauth_redirect_uri('facebook'),
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state,
        ];
        return 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params);
    }
    throw new OAuthError('Provedor de login inválido.');
}

/** GET/POST simples via cURL — mesmo padrão usado em captcha.php/mercadopago_client.php. */
function oauth_http_request(string $url, ?array $post = null): array
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ];
    if ($post !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new OAuthError('Falha ao contatar o provedor de login: ' . $curlError);
    }
    $data = json_decode((string) $raw, true);
    if (!is_array($data) || $status >= 400) {
        throw new OAuthError('O provedor de login retornou uma resposta inesperada.');
    }
    return $data;
}

/** Troca o "code" da URL de callback por um access token. */
function oauth_exchange_code(string $provider, string $code): string
{
    if ($provider === 'google') {
        $data = oauth_http_request('https://oauth2.googleapis.com/token', [
            'client_id' => GOOGLE_OAUTH_CLIENT_ID,
            'client_secret' => GOOGLE_OAUTH_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => oauth_redirect_uri('google'),
            'grant_type' => 'authorization_code',
        ]);
    } elseif ($provider === 'facebook') {
        $data = oauth_http_request('https://graph.facebook.com/v19.0/oauth/access_token', [
            'client_id' => FACEBOOK_OAUTH_APP_ID,
            'client_secret' => FACEBOOK_OAUTH_APP_SECRET,
            'code' => $code,
            'redirect_uri' => oauth_redirect_uri('facebook'),
        ]);
    } else {
        throw new OAuthError('Provedor de login inválido.');
    }

    if (empty($data['access_token'])) {
        throw new OAuthError('Não foi possível concluir o login com ' . oauth_provider_label($provider) . '.');
    }
    return (string) $data['access_token'];
}

/** @return array{id: string, email: ?string, firstName: string, lastName: string, avatarUrl: ?string} */
function oauth_fetch_profile(string $provider, string $accessToken): array
{
    if ($provider === 'google') {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $raw, true);
        if (!is_array($data) || empty($data['sub'])) {
            throw new OAuthError('Não foi possível obter seu perfil do Google.');
        }
        return [
            'id' => (string) $data['sub'],
            'email' => !empty($data['email']) && !empty($data['email_verified']) ? mb_strtolower((string) $data['email']) : null,
            'firstName' => trim((string) ($data['given_name'] ?? '')),
            'lastName' => trim((string) ($data['family_name'] ?? '')),
            'avatarUrl' => $data['picture'] ?? null,
        ];
    }

    if ($provider === 'facebook') {
        $url = 'https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,first_name,last_name,email,picture.type(large)',
            'access_token' => $accessToken,
        ]);
        $data = oauth_http_request($url);
        if (empty($data['id'])) {
            throw new OAuthError('Não foi possível obter seu perfil do Facebook.');
        }
        return [
            'id' => (string) $data['id'],
            'email' => !empty($data['email']) ? mb_strtolower((string) $data['email']) : null,
            'firstName' => trim((string) ($data['first_name'] ?? '')),
            'lastName' => trim((string) ($data['last_name'] ?? '')),
            'avatarUrl' => $data['picture']['data']['url'] ?? null,
        ];
    }

    throw new OAuthError('Provedor de login inválido.');
}

/**
 * Associa/cria a conta local a partir do perfil do provedor e devolve o id
 * do usuário. Prioridade: (1) já tem esse {provider}_id -> usa direto;
 * (2) já existe conta com esse e-mail -> vincula o {provider}_id a ela;
 * (3) cria conta nova, papel USER, com senha aleatória inutilizável (a
 * coluna é NOT NULL e o SQLite não altera isso sem recriar a tabela).
 */
function oauth_login_or_register(string $provider, array $profile): int
{
    $pdo = db();
    $idColumn = $provider . '_id';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE $idColumn = ?");
    $stmt->execute([$profile['id']]);
    $userId = $stmt->fetchColumn();
    if ($userId) {
        return (int) $userId;
    }

    if (!$profile['email']) {
        throw new OAuthError('Não foi possível confirmar seu e-mail junto ao ' . oauth_provider_label($provider) . '. Tente outro método de login ou cadastre-se com e-mail e senha.');
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$profile['email']]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        $pdo->prepare("UPDATE users SET $idColumn = ? WHERE id = ?")->execute([$profile['id'], $existingId]);
        return (int) $existingId;
    }

    $randomPassword = hash_password(bin2hex(random_bytes(32)));
    $stmt = $pdo->prepare("INSERT INTO users
        (first_name, last_name, email, password_hash, role, status, avatar_url, $idColumn)
        VALUES (?,?,?,?,'USER','ACTIVE',?,?)");
    $stmt->execute([
        $profile['firstName'] !== '' ? $profile['firstName'] : 'Usuário',
        $profile['lastName'],
        $profile['email'],
        $randomPassword,
        $profile['avatarUrl'],
        $profile['id'],
    ]);
    return (int) $pdo->lastInsertId();
}
