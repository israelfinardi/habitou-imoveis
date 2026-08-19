<?php
/**
 * Expira anúncios do plano grátis vencidos (expires_at <= agora) e avisa o
 * anunciante por e-mail pra renovar. Assinantes pagos nunca têm expires_at
 * setado (includes/plan_limits.php::compute_listing_expiration), então
 * nunca caem aqui — anúncio pago é eterno.
 *
 * Configure no Cron Jobs do hPanel, ex.: uma vez por hora:
 *
 *   php /home/SEU_USUARIO/public_html/cron/property_expiration.php
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';

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

const EXPIRATION_BATCH_SIZE = 100;

$pdo = db();
$stmt = $pdo->prepare('
    SELECT p.id, p.title, p.advertiser_id, u.first_name, u.email
    FROM properties p
    JOIN users u ON u.id = p.advertiser_id
    WHERE p.status = "PUBLISHED" AND p.expires_at IS NOT NULL AND p.expires_at <= CURRENT_TIMESTAMP
    LIMIT :limit
');
$stmt->bindValue(':limit', EXPIRATION_BATCH_SIZE, PDO::PARAM_INT);
$stmt->execute();
$expired = $stmt->fetchAll();

$results = [];
foreach ($expired as $row) {
    try {
        $pdo->prepare('UPDATE properties SET status = "EXPIRED", deactivated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$row['id']]);

        $html = render_property_expired_email_html($row['first_name'], $row['title']);
        $sent = send_mail($row['email'], 'Seu anúncio expirou — Habitou Imóveis', $html);
        $results[] = ['property_id' => $row['id'], 'notified' => $sent];
    } catch (\Throwable $e) {
        error_log('[property_expiration] Falha ao expirar imóvel ' . $row['id'] . ': ' . $e->getMessage());
        $results[] = ['property_id' => $row['id'], 'notified' => false, 'error' => $e->getMessage()];
    }
}

function render_property_expired_email_html(string $firstName, string $propertyTitle): string
{
    $firstName = e($firstName);
    $propertyTitle = e($propertyTitle);
    $manageUrl = e(base_url('anunciante/imoveis.php'));

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Habitou Imóveis</title></head>
<body style="margin:0;padding:0;background-color:#F7F7F7;font-family:Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F7F7F7;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;">
          <tr>
            <td style="background-color:#C1502E;padding:24px 32px;">
              <span style="font-size:20px;font-weight:700;color:#ffffff;">Habitou Imóveis</span>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;">
              <h1 style="margin:0 0 12px;font-size:20px;color:#222222;">Olá {$firstName}, seu anúncio expirou</h1>
              <p style="margin:0 0 16px;font-size:14px;color:#484848;line-height:1.6;">
                O anúncio <strong>"{$propertyTitle}"</strong> completou 30 dias no ar (validade do plano grátis) e foi
                despublicado automaticamente — ele não aparece mais nas buscas do site até ser renovado.
              </p>
              <p style="margin:0 0 24px;font-size:14px;color:#484848;line-height:1.6;">
                Renove com 1 clique em "Meus imóveis", ou assine um plano pago pra ter anúncios eternos, sem
                precisar renovar.
              </p>
              <a href="{$manageUrl}" style="display:inline-block;background-color:#C1502E;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;padding:12px 22px;border-radius:999px;">Renovar anúncio</a>
            </td>
          </tr>
          <tr>
            <td style="background-color:#F7F7F7;padding:20px 32px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#9B9B9B;">Você recebeu este e-mail porque tem uma conta na Habitou Imóveis.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

echo json_encode(['processed' => count($results), 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
