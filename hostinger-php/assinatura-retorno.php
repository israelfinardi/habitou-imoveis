<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();

$subId = (int) ($_GET['sub'] ?? 0);
$stmt = db()->prepare('SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.id = ? AND s.user_id = ?');
$stmt->execute([$subId, $user['id']]);
$sub = $stmt->fetch();

$result = null;
$error = null;
if ($sub) {
    try {
        $result = activate_subscription_from_preapproval($sub['external_id']);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}
$status = $result['status'] ?? $sub['status'] ?? null;

$pageTitle = 'Confirmando assinatura';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-xl px-4 py-16 text-center sm:px-6 lg:px-8">
  <?php if (!$sub): ?>
    <h1 class="mb-2 text-2xl font-bold">Assinatura não encontrada</h1>
    <p class="text-brand-text-secondary">Não encontramos essa assinatura na sua conta.</p>
  <?php elseif ($error): ?>
    <h1 class="mb-2 text-2xl font-bold">Não foi possível confirmar agora</h1>
    <p class="text-brand-text-secondary">O pagamento pode levar alguns instantes para ser processado pelo Mercado Pago. Assim que for confirmado, o plano <strong><?= e($sub['plan_name']) ?></strong> será ativado automaticamente.</p>
  <?php elseif ($status === 'ACTIVE'): ?>
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-green/10 text-brand-green-hover">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h1 class="mb-2 text-2xl font-bold">Assinatura ativada!</h1>
    <p class="text-brand-text-secondary">Seu plano <strong><?= e($sub['plan_name']) ?></strong> já está ativo.</p>
  <?php elseif ($status === 'CANCELED'): ?>
    <h1 class="mb-2 text-2xl font-bold">Assinatura não concluída</h1>
    <p class="text-brand-text-secondary">O checkout do plano <strong><?= e($sub['plan_name']) ?></strong> foi cancelado.</p>
  <?php else: ?>
    <h1 class="mb-2 text-2xl font-bold">Pagamento em processamento</h1>
    <p class="text-brand-text-secondary">Assim que o Mercado Pago confirmar o pagamento do plano <strong><?= e($sub['plan_name']) ?></strong>, ele será ativado automaticamente. Isso costuma levar poucos minutos.</p>
  <?php endif; ?>
  <a href="<?= base_url('planos.php') ?>" class="mt-8 inline-block rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Ver meus planos</a>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
