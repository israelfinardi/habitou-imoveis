<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$pdo = db();
$stats = [
    'Usuários' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Imóveis' => $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
    'Imobiliárias' => $pdo->query('SELECT COUNT(*) FROM agencies')->fetchColumn(),
    'Contratos' => $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn(),
    'Assinaturas ativas' => $pdo->query('SELECT COUNT(*) FROM subscriptions WHERE status = "ACTIVE"')->fetchColumn(),
    'Feeds VRSync' => $pdo->query('SELECT COUNT(*) FROM feeds')->fetchColumn(),
];

$pageTitle = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside><?php render_admin_nav('index'); ?></aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Visão geral</h1>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <?php foreach ($stats as $label => $value): ?>
          <div class="rounded-xl border border-brand-border bg-white p-5"><p class="text-2xl font-bold"><?= $value ?></p><p class="text-sm text-brand-text-secondary"><?= e($label) ?></p></div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
