<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$pdo = db();
$stats = [
    'Usuários' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'Imóveis' => $pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
    'Imobiliárias' => $pdo->query('SELECT COUNT(*) FROM agencies')->fetchColumn(),
    'Importações de XML' => $pdo->query('SELECT COUNT(*) FROM xml_imports')->fetchColumn(),
    'Assinaturas ativas' => $pdo->query('SELECT COUNT(*) FROM subscriptions WHERE status = "ACTIVE"')->fetchColumn(),
];

$pageTitle = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Visão geral</h1>
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <?php foreach ($stats as $label => $value): ?>
          <div class="rounded-xl border border-brand-border bg-white p-5"><p class="text-2xl font-bold"><?= $value ?></p><p class="text-sm text-brand-text-secondary"><?= e($label) ?></p></div>
        <?php endforeach; ?>
      </div>

      <div class="mt-8 rounded-xl border border-brand-border bg-brand-bg-subtle p-5">
        <h2 class="mb-2 text-sm font-semibold">Token de manutenção</h2>
        <p class="mb-3 text-xs text-brand-text-secondary">
          Gerado automaticamente na primeira vez que o site rodou. Use-o para apagar os dados de
          demonstração em <code class="rounded bg-white px-1 py-0.5"><?= base_url('limpar-dados.php') ?>?token=...</code>
          antes de colocar o catálogo real no ar.
        </p>
        <code class="block break-all rounded-lg border border-brand-border bg-white px-3 py-2 text-xs"><?= e(AUTH_SECRET) ?></code>
      </div>
    </main>
    <aside><?php render_admin_nav('index'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
