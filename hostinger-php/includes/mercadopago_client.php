<?php
/**
 * Cliente HTTP fino para a API REST do Mercado Pago (produto "Assinaturas" /
 * preapproval). Nenhuma credencial fica hardcoded aqui — vem sempre de
 * MP_ACCESS_TOKEN (config/config.php). Lança MercadoPagoException em erro de
 * rede; respostas HTTP não-2xx são devolvidas normalmente pro chamador
 * decidir o que fazer (o corpo do erro do Mercado Pago costuma ser útil).
 */

class MercadoPagoException extends \RuntimeException
{
}

function mp_configured(): bool
{
    return defined('MP_ACCESS_TOKEN') && MP_ACCESS_TOKEN !== '';
}

function mp_request(string $method, string $path, ?array $body = null): array
{
    if (!mp_configured()) {
        throw new MercadoPagoException('Mercado Pago não está configurado (MP_ACCESS_TOKEN em branco em config/config.php).');
    }
    if (!function_exists('curl_init')) {
        throw new MercadoPagoException('A extensão curl do PHP não está habilitada neste servidor.');
    }

    $ch = curl_init('https://api.mercadopago.com' . $path);
    $headers = [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
    ];
    $opts = [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new MercadoPagoException('Falha de conexão com o Mercado Pago: ' . $error);
    }
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    return [
        'status' => $status,
        'ok' => $status >= 200 && $status < 300,
        'body' => is_array($decoded) ? $decoded : [],
    ];
}

/** GET /preapproval_plan/search — todos os planos de assinatura da conta. */
function mp_list_preapproval_plans(): array
{
    $plans = [];
    $offset = 0;
    do {
        $res = mp_request('GET', '/preapproval_plan/search?limit=50&offset=' . $offset);
        if (!$res['ok']) {
            throw new MercadoPagoException('Erro ao listar planos no Mercado Pago (HTTP ' . $res['status'] . '): ' . ($res['body']['message'] ?? 'erro desconhecido'));
        }
        $batch = $res['body']['results'] ?? [];
        $plans = array_merge($plans, $batch);
        $offset += count($batch);
        $total = $res['body']['paging']['total'] ?? count($plans);
    } while (count($batch) > 0 && $offset < $total);

    return $plans;
}

/** POST /preapproval — cria a assinatura de um pagador para um plano específico. */
function mp_create_preapproval(array $data): array
{
    $res = mp_request('POST', '/preapproval', $data);
    if (!$res['ok']) {
        throw new MercadoPagoException('Erro ao criar assinatura no Mercado Pago (HTTP ' . $res['status'] . '): ' . ($res['body']['message'] ?? 'erro desconhecido'));
    }
    return $res['body'];
}

/** GET /preapproval/{id} — status atual de uma assinatura. */
function mp_get_preapproval(string $id): ?array
{
    $res = mp_request('GET', '/preapproval/' . urlencode($id));
    return $res['ok'] ? $res['body'] : null;
}

/** PUT /preapproval/{id} — usado para cancelar (status: cancelled) ou pausar. */
function mp_update_preapproval(string $id, array $data): array
{
    $res = mp_request('PUT', '/preapproval/' . urlencode($id), $data);
    if (!$res['ok']) {
        throw new MercadoPagoException('Erro ao atualizar assinatura no Mercado Pago (HTTP ' . $res['status'] . '): ' . ($res['body']['message'] ?? 'erro desconhecido'));
    }
    return $res['body'];
}

/**
 * Valida o cabeçalho x-signature das notificações de webhook, conforme
 * https://www.mercadopago.com.br/developers/pt/docs/your-integrations/notifications/webhooks#Validando-a-origem-de-uma-notificação
 * Sem MP_WEBHOOK_SECRET configurado, não há como validar — o chamador deve
 * decidir se aceita mesmo assim (a ativação em si só ocorre depois de
 * confirmar o status direto na API, então o pior que uma notificação falsa
 * faz é disparar essa consulta de novo).
 */
function mp_verify_webhook_signature(string $dataId): bool
{
    $sigHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    if (!$sigHeader || !$dataId) {
        return false;
    }

    $parts = [];
    foreach (explode(',', $sigHeader) as $chunk) {
        $pair = explode('=', trim($chunk), 2);
        if (count($pair) === 2) {
            $parts[trim($pair[0])] = trim($pair[1]);
        }
    }
    $ts = $parts['ts'] ?? '';
    $v1 = $parts['v1'] ?? '';
    if (!$ts || !$v1) {
        return false;
    }

    $manifest = 'id:' . strtolower($dataId) . ';request-id:' . $requestId . ';ts:' . $ts . ';';
    $expected = hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET);

    return hash_equals($expected, $v1);
}
