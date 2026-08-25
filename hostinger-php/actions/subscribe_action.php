<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
verify_csrf();

$action = $_POST['do'] ?? '';
$pdo = db();

if ($action === 'subscribe') {
    $planId = (int) $_POST['plan_id'];
    $stmt = $pdo->prepare('SELECT * FROM plans WHERE id = ? AND active = 1');
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        $_SESSION['plans_error'] = 'Plano não encontrado.';
        redirect(base_url('planos.php'));
    }

    if (!isset($_POST['acceptTerms'])) {
        $_SESSION['plans_error'] = 'Você precisa aceitar os Termos do contrato de assinatura para assinar um plano.';
        redirect(base_url('planos.php'));
    }

    if (empty($plan['mp_plan_id'])) {
        // Plano ainda não sincronizado com o Mercado Pago — mantém o fluxo
        // manual antigo (fica PENDING até o admin confirmar em
        // admin/assinaturas.php), pra não quebrar planos criados à mão.
        $pdo->prepare('INSERT INTO subscriptions (user_id, agency_id, plan_id, status) VALUES (?,?,?,"PENDING")')
            ->execute([$user['id'], $user['agency_id'] ?: null, $planId]);
        redirect(base_url('planos.php'));
    }

    try {
        $checkoutUrl = start_subscription_checkout($user, $plan);
    } catch (\Throwable $e) {
        $_SESSION['plans_error'] = 'Não foi possível iniciar o checkout: ' . $e->getMessage();
        redirect(base_url('planos.php'));
    }

    redirect($checkoutUrl);
} elseif ($action === 'cancel') {
    $subId = (int) $_POST['subscription_id'];
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE id = ?');
    $stmt->execute([$subId]);
    $sub = $stmt->fetch();

    $allowed = $sub && (
        $sub['user_id'] == $user['id']
        || (!empty($sub['agency_id']) && $sub['agency_id'] == ($user['agency_id'] ?? null))
        || $user['role'] === 'ADMIN'
    );

    if ($allowed) {
        try {
            cancel_subscription($sub);
        } catch (\Throwable $e) {
            $_SESSION['plans_error'] = 'Não foi possível cancelar no Mercado Pago: ' . $e->getMessage();
        }
    }
}

redirect(base_url('planos.php'));
