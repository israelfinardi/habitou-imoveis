<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$plans = db()->query('SELECT * FROM plans WHERE active = 1 ORDER BY price ASC')->fetchAll();

$currentSubscription = null;
if ($user) {
    $stmt = db()->prepare('SELECT s.*, p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id = s.plan_id
        WHERE (s.user_id = ? OR s.agency_id = ?) AND s.status IN ("ACTIVE","PENDING") ORDER BY s.created_at DESC LIMIT 1');
    $stmt->execute([$user['id'], $user['agency_id'] ?? 0]);
    $currentSubscription = $stmt->fetch() ?: null;
}

$pageTitle = 'Planos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-2 text-center text-3xl font-bold">Planos</h1>
  <p class="mb-10 text-center text-brand-text-secondary">Escolha o plano ideal para anunciar seus imóveis.</p>

  <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
    <?php foreach ($plans as $plan): $isCurrent = $currentSubscription && $currentSubscription['plan_id'] == $plan['id']; $features = json_decode_safe($plan['features']); ?>
      <div class="flex flex-col rounded-2xl border border-brand-border bg-white p-6">
        <h2 class="text-lg font-bold"><?= e($plan['name']) ?></h2>
        <p class="mt-1 text-sm text-brand-text-secondary"><?= e($plan['description']) ?></p>
        <p class="mt-4 text-3xl font-bold text-brand-primary"><?= format_currency_brl($plan['price']) ?><span class="text-sm font-normal text-brand-text-secondary">/mês</span></p>
        <ul class="my-6 flex-1 space-y-2 text-sm">
          <?php foreach ($features as $f): ?><li class="flex items-start gap-2"><span class="text-brand-green">✓</span> <?= e($f) ?></li><?php endforeach; ?>
        </ul>
        <?php if (!$user): ?>
          <a href="<?= base_url('login.php') ?>" class="w-full rounded-full bg-brand-primary py-2.5 text-center text-sm font-semibold text-white hover:bg-brand-primary-hover">Assinar plano</a>
        <?php elseif ($isCurrent): ?>
          <form method="post" action="<?= base_url('actions/subscribe_action.php') ?>">
            <?= csrf_field() ?><input type="hidden" name="do" value="cancel"><input type="hidden" name="subscription_id" value="<?= $currentSubscription['id'] ?>">
            <button class="w-full rounded-full border border-brand-border py-2.5 text-sm font-semibold text-brand-text-secondary hover:border-red-400 hover:text-red-600">Cancelar assinatura</button>
          </form>
          <p class="mt-2 text-center text-xs text-brand-text-secondary">Status: <?= $currentSubscription['status'] === 'ACTIVE' ? 'ativo' : 'aguardando confirmação de pagamento' ?></p>
        <?php else: ?>
          <form method="post" action="<?= base_url('actions/subscribe_action.php') ?>">
            <?= csrf_field() ?><input type="hidden" name="do" value="subscribe"><input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
            <button class="w-full rounded-full bg-brand-primary py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Assinar plano</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
