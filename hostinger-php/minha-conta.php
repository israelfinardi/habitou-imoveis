<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/account_nav.php';

$user = require_login();
$pdo = db();

$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE advertiser_id = ?');
$stmtCount->execute([$user['id']]);
$propertyCount = (int) $stmtCount->fetchColumn();

$stmtFav = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
$stmtFav->execute([$user['id']]);
$favCount = (int) $stmtFav->fetchColumn();

$stmt = $pdo->prepare('SELECT s.*, pl.name AS plan_name FROM subscriptions s JOIN plans pl ON pl.id = s.plan_id WHERE s.user_id = ? AND s.status = "ACTIVE" LIMIT 1');
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch();

$pageTitle = 'Minha conta';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <div class="mb-4 flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary text-sm font-semibold text-white"><?= e(mb_strtoupper(mb_substr($user['first_name'], 0, 1))) ?></span>
        <div>
          <p class="text-sm font-semibold"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
          <p class="text-xs text-brand-text-secondary"><?= e($user['email']) ?></p>
        </div>
      </div>
      <?php render_account_nav('overview'); ?>
    </aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Olá, <?= e($user['first_name']) ?>!</h1>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
          <p class="text-2xl font-bold"><?= $propertyCount ?></p>
          <p class="text-sm text-brand-text-secondary">Meus anúncios</p>
        </a>
        <a href="<?= base_url('minha-conta-favoritos.php') ?>" class="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
          <p class="text-2xl font-bold"><?= $favCount ?></p>
          <p class="text-sm text-brand-text-secondary">Imóveis favoritos</p>
        </a>
        <div class="rounded-xl border border-brand-border bg-white p-5">
          <p class="text-2xl font-bold"><?= e($subscription['plan_name'] ?? 'Nenhum') ?></p>
          <p class="text-sm text-brand-text-secondary">Plano atual</p>
          <?php if (!$subscription): ?><a href="<?= base_url('planos.php') ?>" class="mt-1 inline-block text-xs font-medium text-brand-primary hover:underline">Ver planos</a><?php endif; ?>
        </div>
      </div>

      <div class="mt-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Dados da conta</h2>
        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <div><dt class="text-brand-text-secondary">Nome</dt><dd><?= e($user['first_name'] . ' ' . $user['last_name']) ?></dd></div>
          <div><dt class="text-brand-text-secondary">E-mail</dt><dd><?= e($user['email']) ?></dd></div>
          <div><dt class="text-brand-text-secondary">Telefone</dt><dd><?= e($user['phone'] ?: '—') ?></dd></div>
          <div><dt class="text-brand-text-secondary">Tipo de conta</dt><dd><?= e($user['role']) ?></dd></div>
        </dl>
        <a href="<?= base_url('minha-conta-dados.php') ?>" class="mt-4 inline-block text-sm font-medium text-brand-primary hover:underline">Editar dados</a>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
