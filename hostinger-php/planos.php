<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$plans = db()->query('SELECT * FROM plans WHERE active = 1 ORDER BY price ASC')->fetchAll();
$plansError = $_SESSION['plans_error'] ?? null;
unset($_SESSION['plans_error']);

$currentSubscription = null;
if ($user) {
    // Aspas simples nos literais: plans tem uma coluna `active`, e o SQLite
    // resolveria "ACTIVE" (aspas duplas) como referência a essa coluna em vez
    // do texto, quebrando a comparação sem erro nenhum — ver includes/plan_limits.php.
    $stmt = db()->prepare("SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id
        WHERE (s.user_id = ? OR s.agency_id = ?) AND s.status IN ('ACTIVE','PENDING') ORDER BY s.created_at DESC LIMIT 1");
    $stmt->execute([$user['id'], $user['agency_id'] ?? 0]);
    $currentSubscription = $stmt->fetch() ?: null;
}

$pageTitle = 'Planos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-12 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 <?= $user ? 'lg:grid-cols-[1fr_220px]' : '' ?>">
    <main class="mx-auto w-full max-w-5xl">
      <h1 class="mb-2 text-center text-3xl font-bold">Planos para anunciar mais imóveis</h1>
      <p class="mx-auto mb-2 max-w-xl text-center text-brand-text-secondary">Escolha quantos imóveis você precisa anunciar ao mesmo tempo. Sem fidelidade — mude ou cancele quando quiser.</p>
      <?php if ($plansError): ?><p class="mx-auto mb-4 max-w-xl rounded-lg bg-red-50 px-3 py-2 text-center text-sm text-red-700"><?= e($plansError) ?></p><?php endif; ?>
      <?php if (!$user): ?>
        <p class="mb-10 text-center text-sm text-brand-text-secondary">Ainda não tem conta? <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="font-medium text-brand-primary hover:underline">Cadastre sua imobiliária</a> ou <a href="<?= base_url('cadastro.php?tipo=corretor') ?>" class="font-medium text-brand-primary hover:underline">seu perfil de corretor</a>.</p>
      <?php else: ?>
        <div class="mb-10"></div>
      <?php endif; ?>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
    <?php foreach ($plans as $plan): $isCurrent = $currentSubscription && $currentSubscription['plan_id'] == $plan['id']; $features = json_decode_safe($plan['features']); ?>
      <div class="relative flex flex-col rounded-2xl border <?= $isCurrent ? 'border-brand-primary' : 'border-brand-border' ?> bg-white p-6">
        <?php if ($isCurrent): ?>
          <span class="absolute -top-3 left-6 rounded-full bg-brand-primary px-3 py-1 text-xs font-semibold text-white">Seu plano atual</span>
        <?php endif; ?>
        <h2 class="text-lg font-bold"><?= e($plan['name']) ?></h2>
        <p class="mt-1 text-sm text-brand-text-secondary"><?= e($plan['description']) ?></p>
        <p class="mt-4 text-3xl font-bold text-brand-primary"><?= format_currency_brl($plan['price']) ?><span class="text-sm font-normal text-brand-text-secondary">/mês</span></p>
        <ul class="my-6 flex-1 space-y-2 text-sm">
          <?php foreach ($features as $f): ?><li class="flex items-start gap-2"><span class="text-brand-green">✓</span> <?= e($f) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!$user): ?>
          <a href="<?= base_url('login.php') ?>" class="w-full rounded-full bg-brand-primary py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-primary-hover">Assinar plano</a>
        <?php elseif ($isCurrent): ?>
          <form method="post" action="<?= base_url('actions/subscribe_action.php') ?>" onsubmit="return confirm('Cancelar sua assinatura do plano <?= e(addslashes($plan['name'])) ?>?');">
            <?= csrf_field() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="subscription_id" value="<?= $currentSubscription['id'] ?>">
            <button class="w-full rounded-full border border-brand-border py-2.5 text-sm font-semibold text-brand-text-secondary hover:border-red-400 hover:text-red-600">Cancelar assinatura</button>
          </form>
          <p class="mt-2 text-center text-xs text-brand-text-secondary">Status: <?= $currentSubscription['status'] === 'ACTIVE' ? 'ativo' : 'aguardando confirmação de pagamento' ?></p>
        <?php else: ?>
          <form method="post" action="<?= base_url('actions/subscribe_action.php') ?>">
            <?= csrf_field() ?><input type="hidden" name="do" value="subscribe"><input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
            <label class="mb-3 flex items-start gap-2 text-left text-xs text-brand-text-secondary">
              <input type="checkbox" name="acceptTerms" required class="mt-0.5 h-4 w-4 shrink-0 rounded border-brand-border text-brand-primary focus:ring-brand-primary">
              <span>Li e aceito os <a href="<?= base_url('termos-assinatura.php') ?>" target="_blank" class="font-medium text-brand-primary hover:underline">Termos do contrato de assinatura</a>.</span>
            </label>
            <button class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover"><?= $currentSubscription ? 'Trocar para este plano' : 'Assinar plano' ?></button>
          </form>
          <?php if (!$plan['mp_plan_id']): ?>
            <p class="mt-2 text-center text-xs text-brand-text-secondary">Ativado manualmente pelo administrador após confirmação do pagamento.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
      </div>
      <p class="mx-auto mt-8 max-w-2xl text-center text-xs text-brand-text-secondary">
        Pagamento processado com segurança pelo Mercado Pago. Ao assinar, você é redirecionado ao
        checkout — a assinatura é ativada automaticamente assim que o pagamento é confirmado.
      </p>
    </main>
    <?php if ($user): ?><aside><?php render_account_nav('planos'); ?></aside><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
