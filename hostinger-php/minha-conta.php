<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/account_nav.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$pdo = db();

$stmtCount = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE advertiser_id = ?');
$stmtCount->execute([$user['id']]);
$propertyCount = (int) $stmtCount->fetchColumn();

$stmtFav = $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
$stmtFav->execute([$user['id']]);
$favCount = (int) $stmtFav->fetchColumn();

$contractCount = count(list_contracts_for_user($user));

// Aspas simples no literal: plans tem uma coluna `active`, e o SQLite
// resolveria "ACTIVE" (aspas duplas) como referência a essa coluna em vez
// do texto, quebrando a comparação sem erro nenhum — ver includes/plan_limits.php.
$stmt = $pdo->prepare("SELECT s.*, pl.name AS plan_name FROM subscriptions s JOIN plans pl ON pl.id = s.plan_id WHERE s.user_id = ? AND s.status = 'ACTIVE' LIMIT 1");
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch();

$profileFields = ['phone', 'address', 'zip_code', 'city', 'instagram', 'facebook', 'creci', 'cnpj', 'bio', 'avatar_url'];
$profileFilled = count(array_filter($profileFields, fn ($f) => !empty($user[$f])));

$agency = null;
if ($user['role'] === 'AGENCY_ADMIN' && $user['agency_id']) {
    $stmtAgency = $pdo->prepare('SELECT * FROM agencies WHERE id = ?');
    $stmtAgency->execute([$user['agency_id']]);
    $agency = $stmtAgency->fetch();
}

// Card de resumo por item do menu (estilo "visão geral" do Facebook/Twitter,
// cada card representa uma página acessível no dropdown/sidebar — ver
// includes/account_nav.php, a mesma fonte usada ali).
$summaryCards = [
    'anuncios' => ['value' => (string) $propertyCount, 'label' => 'Meus anúncios'],
    'favoritos' => ['value' => (string) $favCount, 'label' => 'Imóveis favoritos'],
    'contratos' => ['value' => (string) $contractCount, 'label' => 'Contratos'],
    'planos' => ['value' => e($subscription['plan_name'] ?? 'Nenhum'), 'label' => 'Plano atual'],
    'dados' => ['value' => $profileFilled . '/' . count($profileFields), 'label' => 'Perfil preenchido'],
];
if ($agency) {
    $summaryCards['imobiliaria'] = ['value' => e($agency['name']), 'label' => 'Imobiliária · ' . e($agency['status'])];
}

$pageTitle = 'Minha conta';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center gap-4">
        <div class="relative h-16 w-16 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= e($user['avatar_url']) ?>" class="h-full w-full object-cover" alt="">
          <?php else: ?>
            <span class="flex h-full w-full items-center justify-center text-xl font-semibold text-brand-primary"><?= e(mb_strtoupper(mb_substr($user['first_name'], 0, 1))) ?></span>
          <?php endif; ?>
        </div>
        <div>
          <h1 class="text-2xl font-bold">Olá, <?= e($user['first_name']) ?>!</h1>
          <p class="text-sm text-brand-text-secondary"><?= e($user['email']) ?></p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach (account_nav_items() as $key => [$href, $label, $icon]):
          if ($key === 'overview' || $key === 'senha') continue;
          $card = $summaryCards[$key] ?? null;
        ?>
        <a href="<?= e(base_url($href)) ?>" class="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
          <div class="mb-2 flex items-center gap-2 text-brand-text-secondary"><?= render_nav_icon($icon) ?><span class="text-xs font-semibold uppercase"><?= e($label) ?></span></div>
          <?php if ($card): ?>
            <p class="truncate text-xl font-bold"><?= $card['value'] ?></p>
            <p class="text-sm text-brand-text-secondary"><?= $card['label'] ?></p>
          <?php else: ?>
            <p class="text-sm text-brand-text-secondary">Ver <?= mb_strtolower(e($label)) ?></p>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </main>
    <aside>
      <div class="mb-4 flex items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-primary text-sm font-semibold text-white">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= e($user['avatar_url']) ?>" class="h-full w-full object-cover" alt="">
          <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($user['first_name'], 0, 1))) ?>
          <?php endif; ?>
        </span>
        <div>
          <p class="text-sm font-semibold"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
          <p class="text-xs text-brand-text-secondary"><?= e($user['email']) ?></p>
        </div>
      </div>
      <?php render_account_nav('overview'); ?>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
