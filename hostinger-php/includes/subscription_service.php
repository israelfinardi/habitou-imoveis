<?php
require_once __DIR__ . '/mercadopago_client.php';

/**
 * Camada de negócio das assinaturas pagas (planos.php, admin/planos.php,
 * admin/assinaturas.php, actions/subscribe_action.php, o retorno do
 * checkout e o webhook). Mantém o banco local como cópia — a verdade sobre
 * o status de uma assinatura é sempre consultada na API do Mercado Pago
 * (mp_get_preapproval), nunca aceita de parâmetros vindos do navegador ou
 * do corpo de uma notificação de webhook, pra não dar pra falsificar uma
 * ativação.
 */

function mp_status_to_local(string $mpStatus): string
{
    return match ($mpStatus) {
        'authorized' => 'ACTIVE',
        'cancelled' => 'CANCELED',
        'paused' => 'CANCELED', // assinatura pausada não cobra mais — trata como inativa
        default => 'PENDING',
    };
}

/**
 * Busca os planos de assinatura cadastrados na conta Mercado Pago do
 * anunciante (produto "Assinaturas" no painel deles) e espelha em `plans`,
 * casando pelo mp_plan_id. Planos criados manualmente no admin (sem
 * mp_plan_id) não são tocados.
 */
function sync_plans_from_mercadopago(): array
{
    $pdo = db();
    $mpPlans = mp_list_preapproval_plans();
    $created = 0;
    $updated = 0;

    foreach ($mpPlans as $mpPlan) {
        $mpId = $mpPlan['id'] ?? null;
        if (!$mpId) {
            continue;
        }
        $name = $mpPlan['reason'] ?? ('Plano ' . $mpId);
        $recurring = $mpPlan['auto_recurring'] ?? [];
        $price = (float) ($recurring['transaction_amount'] ?? 0);
        $frequency = (int) ($recurring['frequency'] ?? 1);
        $frequencyType = $recurring['frequency_type'] ?? 'months';
        $billingPeriod = ($frequency === 1 && $frequencyType === 'months') ? 'MONTHLY' : strtoupper($frequency . '_' . $frequencyType);
        $active = ($mpPlan['status'] ?? '') === 'active' ? 1 : 0;

        $stmt = $pdo->prepare('SELECT id FROM plans WHERE mp_plan_id = ?');
        $stmt->execute([$mpId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $pdo->prepare('UPDATE plans SET name = ?, price = ?, billing_period = ?, active = ? WHERE id = ?')
                ->execute([$name, $price, $billingPeriod, $active, $existingId]);
            $updated++;
        } else {
            $slug = slugify($name);
            $suffix = $pdo->prepare('SELECT COUNT(*) FROM plans WHERE slug = ?');
            $suffix->execute([$slug]);
            if ($suffix->fetchColumn() > 0) {
                $slug .= '-' . substr((string) $mpId, -6);
            }
            $pdo->prepare('INSERT INTO plans (name, slug, description, price, billing_period, features, active, mp_plan_id) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name, $slug, null, $price, $billingPeriod, '[]', $active, $mpId]);
            $created++;
        }
    }

    return ['total' => count($mpPlans), 'created' => $created, 'updated' => $updated];
}

/**
 * Cria, na conta Mercado Pago, o plano de assinatura correspondente a um
 * plano local criado manualmente pelo admin (nome/preço/etc já definidos
 * aqui), e liga os dois salvando o mp_plan_id retornado — usado pelo botão
 * "Publicar no Mercado Pago" em admin/planos.php, pra planos manuais que
 * ainda não existem do lado do Mercado Pago.
 */
function publish_plan_to_mercadopago(array $plan): void
{
    $result = mp_create_preapproval_plan([
        'reason' => $plan['name'],
        'auto_recurring' => [
            'frequency' => 1,
            'frequency_type' => 'months',
            'transaction_amount' => (float) $plan['price'],
            'currency_id' => 'BRL',
        ],
        'back_url' => base_url('planos.php'),
    ]);

    db()->prepare('UPDATE plans SET mp_plan_id = ? WHERE id = ?')->execute([$result['id'], $plan['id']]);
}

/**
 * Cria a assinatura no Mercado Pago para o usuário/plano e devolve a URL de
 * checkout (init_point) pra onde o usuário deve ser redirecionado. Grava
 * uma linha local em status PENDING antes de chamar a API, pra já ter um id
 * local pra usar na back_url — se a chamada à API falhar, a linha é
 * removida.
 */
function start_subscription_checkout(array $user, array $plan): string
{
    if (empty($plan['mp_plan_id'])) {
        throw new \RuntimeException('Este plano ainda não está sincronizado com o Mercado Pago.');
    }

    $pdo = db();
    $pdo->prepare('INSERT INTO subscriptions (user_id, agency_id, plan_id, status) VALUES (?,?,?,"PENDING")')
        ->execute([$user['id'], $user['agency_id'] ?: null, $plan['id']]);
    $localId = (int) $pdo->lastInsertId();

    try {
        $result = mp_create_preapproval([
            'preapproval_plan_id' => $plan['mp_plan_id'],
            'reason' => $plan['name'],
            'external_reference' => 'habitou-' . $user['id'] . '-' . $plan['id'] . '-' . $localId,
            'payer_email' => $user['email'],
            'back_url' => base_url('assinatura-retorno.php?sub=' . $localId),
            'notification_url' => base_url('actions/mercadopago_webhook.php'),
            'status' => 'pending',
        ]);
    } catch (MercadoPagoException $e) {
        $pdo->prepare('DELETE FROM subscriptions WHERE id = ?')->execute([$localId]);
        throw $e;
    }

    $initPoint = $result['init_point'] ?? null;
    if (!$initPoint) {
        $pdo->prepare('DELETE FROM subscriptions WHERE id = ?')->execute([$localId]);
        throw new MercadoPagoException('O Mercado Pago não retornou uma URL de checkout.');
    }

    $pdo->prepare('UPDATE subscriptions SET external_id = ? WHERE id = ?')->execute([$result['id'], $localId]);

    return $initPoint;
}

/**
 * Confere na API do Mercado Pago o status real de uma assinatura (nunca
 * confia em valores vindos do retorno do checkout ou do corpo do webhook) e
 * espelha no banco local. Ao ativar um plano, cancela qualquer outra
 * assinatura ACTIVE/PENDING do mesmo usuário/imobiliária, já que só um
 * plano fica valendo por vez.
 */
function activate_subscription_from_preapproval(?string $preapprovalId): ?array
{
    if (!$preapprovalId) {
        return null;
    }

    $mp = mp_get_preapproval($preapprovalId);
    if (!$mp) {
        return null;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE external_id = ?');
    $stmt->execute([$preapprovalId]);
    $sub = $stmt->fetch();
    if (!$sub) {
        return null;
    }

    $newStatus = mp_status_to_local($mp['status'] ?? '');
    if ($newStatus === $sub['status']) {
        return $sub;
    }

    if ($newStatus === 'ACTIVE') {
        $pdo->prepare('UPDATE subscriptions SET status = "ACTIVE", started_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$sub['id']]);

        $others = $pdo->prepare('SELECT * FROM subscriptions WHERE id != ? AND status IN ("ACTIVE","PENDING") AND ((user_id = ? AND user_id IS NOT NULL) OR (agency_id = ? AND agency_id IS NOT NULL))');
        $others->execute([$sub['id'], $sub['user_id'], $sub['agency_id']]);
        foreach ($others->fetchAll() as $other) {
            try {
                cancel_subscription($other);
            } catch (MercadoPagoException $e) {
                // Segue mesmo se o cancelamento remoto da assinatura antiga falhar —
                // a nova já está ativa; o admin pode cancelar a antiga manualmente.
            }
        }
    } elseif ($newStatus === 'CANCELED') {
        $pdo->prepare('UPDATE subscriptions SET status = "CANCELED", canceled_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$sub['id']]);
    }

    $stmt->execute([$preapprovalId]);
    return $stmt->fetch() ?: null;
}

/**
 * Cancela no Mercado Pago (quando a assinatura tem external_id) e só marca
 * como cancelada localmente depois da confirmação — se a chamada à API
 * falhar, a exceção sobe e a assinatura continua ativa no banco local,
 * evitando que o cliente perca acesso enquanto continua sendo cobrado.
 */
function cancel_subscription(array $subscription): void
{
    if (!empty($subscription['external_id'])) {
        mp_update_preapproval($subscription['external_id'], ['status' => 'cancelled']);
    }
    db()->prepare('UPDATE subscriptions SET status = "CANCELED", canceled_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([$subscription['id']]);
}
