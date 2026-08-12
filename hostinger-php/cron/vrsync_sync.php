<?php
/**
 * Executa a sincronização automática dos feeds VRSync cujo próximo horário
 * já chegou. Configure um cron job na Hostinger (hPanel > Avançado > Cron
 * Jobs) apontando para este script, por exemplo a cada hora:
 *
 *   php /home/SEU_USUARIO/public_html/cron/vrsync_sync.php
 *
 * Ou, se preferir HTTP, chame esta URL com o cabeçalho Authorization.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/vrsync_sync.php';

$isCli = php_sapi_name() === 'cli';
if (!$isCli) {
    $secret = defined('CRON_SECRET') ? CRON_SECRET : '';
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($secret && $auth !== 'Bearer ' . $secret) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    header('Content-Type: application/json');
}

$stmt = db()->query('SELECT id, name FROM feeds WHERE status = "ACTIVE" AND (next_sync_at IS NULL OR next_sync_at <= CURRENT_TIMESTAMP)');
$feeds = $stmt->fetchAll();

$results = [];
foreach ($feeds as $feed) {
    try {
        $log = run_feed_sync((int) $feed['id']);
        $results[] = ['feed_id' => $feed['id'], 'name' => $feed['name'], 'status' => $log['status']];
    } catch (\Throwable $e) {
        $results[] = ['feed_id' => $feed['id'], 'name' => $feed['name'], 'status' => 'ERROR', 'error' => $e->getMessage()];
    }
}

$output = json_encode(['processed' => count($results), 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $output . "\n";
