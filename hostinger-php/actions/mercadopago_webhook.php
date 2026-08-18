<?php
/**
 * Recebe as notificações do Mercado Pago quando o status de uma assinatura
 * muda (pagamento aprovado, cancelamento feito pelo pagador direto no
 * Mercado Pago, etc.) — é o que garante que o banco local fica em dia
 * mesmo quando o cliente não volta pra url de retorno do checkout. Nunca
 * confia no corpo da notificação: só usa o id pra ir buscar o status real
 * na API (activate_subscription_from_preapproval).
 */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$body = is_array($body) ? $body : [];

$type = $_GET['type'] ?? $_GET['topic'] ?? $body['type'] ?? '';
$preapprovalId = $_GET['data.id'] ?? $_GET['id'] ?? ($body['data']['id'] ?? null);

if (defined('MP_WEBHOOK_SECRET') && MP_WEBHOOK_SECRET !== '') {
    if (!mp_verify_webhook_signature((string) $preapprovalId)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid signature']);
        exit;
    }
}

if (in_array($type, ['preapproval', 'subscription_preapproval'], true) && $preapprovalId) {
    try {
        activate_subscription_from_preapproval((string) $preapprovalId);
    } catch (\Throwable $e) {
        error_log('mercadopago_webhook: ' . $e->getMessage());
    }
}

http_response_code(200);
echo json_encode(['received' => true]);
