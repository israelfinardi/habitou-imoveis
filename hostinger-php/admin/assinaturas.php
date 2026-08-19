<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$subs = db()->query("SELECT s.*, p.name AS plan_name, u.first_name, u.last_name, u.email, ag.name AS agency_name
    FROM subscriptions s JOIN plans p ON p.id = s.plan_id LEFT JOIN users u ON u.id = s.user_id LEFT JOIN agencies ag ON ag.id = s.agency_id
    ORDER BY s.created_at DESC LIMIT 200")->fetchAll();
$statuses = ['PENDING', 'ACTIVE', 'CANCELED', 'EXPIRED'];

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('assinaturas.csv', [
        'subscriber' => 'Assinante', 'plan_name' => 'Plano', 'origin' => 'Origem', 'created_at' => 'Criada em', 'status' => 'Status',
    ], array_map(fn($s) => [
        'subscriber' => $s['agency_name'] ?? ($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['email'] . ')'),
        'plan_name' => $s['plan_name'], 'origin' => $s['external_id'] ?: 'Manual', 'created_at' => $s['created_at'], 'status' => $s['status'],
    ], $subs));
}

$pageTitle = 'Assinaturas (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Assinaturas (<?= count($subs) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <p class="mb-4 text-sm text-brand-text-secondary">Assinaturas com um plano do Mercado Pago são ativadas/canceladas automaticamente pelo checkout e pelo webhook. O status abaixo também pode ser ajustado manualmente aqui (útil para planos sem gateway).</p>
      <?php if (empty($subs)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhuma assinatura encontrada.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($subs as $s): ?>
            <div class="rounded-2xl border border-brand-border bg-white p-4">
              <p class="mb-0.5 truncate text-sm font-semibold text-brand-text"><?= e($s['agency_name'] ?? ($s['first_name'] . ' ' . $s['last_name'])) ?></p>
              <?php if (!$s['agency_name']): ?><p class="mb-2 truncate text-xs text-brand-text-secondary"><?= e($s['email']) ?></p><?php endif; ?>
              <p class="mb-1 text-sm text-brand-text"><?= e($s['plan_name']) ?></p>
              <p class="mb-3 text-xs text-brand-text-secondary">
                <?= $s['external_id'] ? e($s['external_id']) : 'Manual' ?> · <?= format_date($s['created_at']) ?>
              </p>
              <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                <?= csrf_field() ?><input type="hidden" name="do" value="update_subscription_status"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <select name="status" class="w-full rounded-lg border border-brand-border px-2 py-1.5 text-xs"><?php foreach ($statuses as $st): ?><option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('assinaturas'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
