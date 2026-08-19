<?php
/**
 * Dispara e-mails de recomendação de imóveis para usuários comuns
 * (compradores/locatários — corretores, imobiliárias e admins nunca entram
 * aqui, ver eligible_recommendation_users() em includes/recommendation_service.php).
 *
 * Sem Redis/BullMQ na Hostinger compartilhada: este script processa um
 * lote pequeno por execução (RECOMMENDATION_BATCH_SIZE) e deve ser chamado
 * periodicamente pelo Cron Jobs do hPanel — ex., a cada 15 minutos:
 *
 *   php /home/SEU_USUARIO/public_html/cron/property_recommendations.php
 *
 * Cada usuário recebe no máximo 1 e-mail por dia (throttle em
 * recommendation_email_log). Rodando a cada 15 min, um lote de 40 dá conta
 * de até ~3.840 usuários/dia sem nenhuma requisição PHP demorada.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/recommendation_service.php';

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

const RECOMMENDATION_BATCH_SIZE = 40;
const RECOMMENDATION_MIN_ITEMS = 3;

$users = eligible_recommendation_users(RECOMMENDATION_BATCH_SIZE);
$results = [];

foreach ($users as $user) {
    $userId = (int) $user['id'];
    try {
        $profile = build_user_interest_profile($userId);
        $properties = $profile ? find_recommendations_for_user($userId, $profile, previously_sent_property_ids($userId)) : [];

        if (count($properties) < RECOMMENDATION_MIN_ITEMS) {
            mark_recommendation_sent($userId, []);
            $results[] = ['user_id' => $userId, 'sent' => false, 'reason' => 'poucas recomendações disponíveis'];
            continue;
        }

        $html = render_recommendation_email_html($user, $properties);
        $sent = send_mail($user['email'], 'Encontramos imóveis para você — Habitou Imóveis', $html);
        mark_recommendation_sent($userId, array_column($properties, 'id'));
        $results[] = ['user_id' => $userId, 'sent' => $sent, 'properties' => count($properties)];
    } catch (\Throwable $e) {
        error_log('[property_recommendations] Falha ao processar usuário ' . $userId . ': ' . $e->getMessage());
        $results[] = ['user_id' => $userId, 'sent' => false, 'error' => $e->getMessage()];
    }
}

echo json_encode(['processed' => count($results), 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
