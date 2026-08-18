<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$subs = db()->query("SELECT s.*, p.name AS plan_name, u.first_name, u.last_name, u.email, ag.name AS agency_name
    FROM subscriptions s JOIN plans p ON p.id = s.plan_id LEFT JOIN users u ON u.id = s.user_id LEFT JOIN agencies ag ON ag.id = s.agency_id
    ORDER BY s.created_at DESC LIMIT 200")->fetchAll();
$statuses = ['PENDING', 'ACTIVE', 'CANCELED', 'EXPIRED'];

$pageTitle = 'Assinaturas (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Assinaturas (<?= count($subs) ?>)</h1>
      <p class="mb-4 text-sm text-brand-text-secondary">Assinaturas com um plano do Mercado Pago são ativadas/canceladas automaticamente pelo checkout e pelo webhook. O status abaixo também pode ser ajustado manualmente aqui (útil para planos sem gateway).</p>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary"><tr><th class="px-4 py-3">Assinante</th><th class="px-4 py-3">Plano</th><th class="px-4 py-3">Origem</th><th class="px-4 py-3">Criada em</th><th class="px-4 py-3">Status</th></tr></thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($subs as $s): ?>
              <tr>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= e($s['agency_name'] ?? ($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['email'] . ')')) ?></td>
                <td class="px-4 py-3"><?= e($s['plan_name']) ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= $s['external_id'] ? e($s['external_id']) : 'Manual' ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= format_date($s['created_at']) ?></td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onchange="this.submit()">
                    <?= csrf_field() ?><input type="hidden" name="do" value="update_subscription_status"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <select name="status" class="rounded-lg border border-brand-border px-2 py-1 text-xs"><?php foreach ($statuses as $st): ?><option value="<?= $st ?>" <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <aside><?php render_admin_nav('assinaturas'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
