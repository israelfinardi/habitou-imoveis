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
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'no-reply@habitou.com.br');
define('SMTP_FROM_NAME', 'Habitou Imóveis');

define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024);

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // mude para '1' apenas para depurar
ini_set('log_errors', '1');
