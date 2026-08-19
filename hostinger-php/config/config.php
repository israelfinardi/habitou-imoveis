<?php
// Configuração do site. Não precisa editar nada aqui pra rodar: o banco de
// dados (SQLite) e os segredos de segurança são criados sozinhos na primeira
// vez que o site é acessado. Só mexa aqui se quiser personalizar algo
// (nome do site, SMTP, limite de upload).

define('APP_NAME', 'Habitou Imóveis');
define('DATA_DIR', __DIR__ . '/../data');

// --- URL do site: detectada automaticamente a partir do domínio acessado ---
if (!empty($_SERVER['HTTP_HOST'])) {
    $__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    define('APP_URL', ($__isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
} else {
    // contexto de linha de comando (cron) — sem efeito prático nos links gerados
    define('APP_URL', 'http://localhost');
}

// --- Segredos de segurança: gerados automaticamente e guardados em
// data/.secrets.php (fora do alcance do navegador, protegido por .htaccess) ---
function habitou_secret(string $name): string
{
    static $secrets = null;
    $path = DATA_DIR . '/.secrets.php';
    if ($secrets === null) {
        $secrets = is_file($path) ? (require $path) : [];
        if (!is_array($secrets)) {
            $secrets = [];
        }
    }
    if (empty($secrets[$name])) {
        $secrets[$name] = bin2hex(random_bytes(24));
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
        }
        file_put_contents($path, "<?php\nreturn " . var_export($secrets, true) . ";\n");
    }
    return $secrets[$name];
}
define('AUTH_SECRET', habitou_secret('auth'));
define('CRON_SECRET', habitou_secret('cron'));

// --- E-mail (opcional): deixe em branco para usar a função mail() nativa do PHP ---
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contato@habitou.com.br');
define('SMTP_PASS', '44b20760acf8db6f54d9358f1cb0b0a6c6ca79cd4b8cd5b5251e046225b58312'); // token API da Hostinger, não a senha da caixa de e-mail
define('SMTP_FROM', 'contato@habitou.com.br');
define('SMTP_FROM_NAME', 'Habitou Imóveis');

// --- Mercado Pago (assinaturas): cole aqui as credenciais de PRODUÇÃO da
// sua aplicação, em https://www.mercadopago.com.br/developers/panel/app
// > Credenciais de produção. O Access Token dá acesso total à sua conta —
// nunca cole essas chaves em nenhum outro lugar (chat, planilha, etc.) além
// deste arquivo, que não é servido pelo navegador.
define('MP_ACCESS_TOKEN', 'APP_USR-6630885330388333-110502-12039fead3f9bfee39c7efdc4c86faae-150146897');
define('MP_PUBLIC_KEY', 'APP_USR-eaed27b6-b742-4262-8cf5-bb32ceb80ee8');
// Opcional: "Chave secreta" configurada em Webhooks no painel do Mercado
// Pago, usada para validar a assinatura das notificações recebidas em
// actions/mercadopago_webhook.php. Deixe em branco se ainda não configurou.
define('MP_WEBHOOK_SECRET', '');

// --- Cloudflare Turnstile (opcional): desafio invisível anti-bot no login
// e cadastro. Crie um site em https://dash.cloudflare.com/?to=/:account/turnstile
// (modo "Managed" ou "Invisible") e cole as duas chaves abaixo. Em branco,
// o desafio simplesmente não aparece — login/cadastro continuam funcionando
// normalmente, só sem essa camada extra.
define('TURNSTILE_SITE_KEY', '');
define('TURNSTILE_SECRET_KEY', '');

define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024);

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // mude para '1' apenas para depurar
ini_set('log_errors', '1');
