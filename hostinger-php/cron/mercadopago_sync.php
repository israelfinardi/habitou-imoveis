<?php
/**
 * Sincroniza os planos de assinatura cadastrados na conta do Mercado Pago
 * com a tabela local `plans`. Configure um cron job na Hostinger (hPanel >
 * Avançado > Cron Jobs), por exemplo uma vez por dia:
 *
 *   php /home/SEU_USUARIO/public_html/cron/mercadopago_sync.php
 *
 * Ou, se preferir HTTP, chame esta URL com o cabeçalho Authorization.
 * Também dá pra sincronizar manualmente a qualquer momento em
 * admin/planos.php.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

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

try {
    $result = sync_plans_from_mercadopago();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
