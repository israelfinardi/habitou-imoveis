<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$q = trim($_GET['q'] ?? '');
$pdo = db();

// Junta o plano ativo (se houver) de cada usuário via subquery correlacionada
// — pega só a assinatura ACTIVE mais recente daquele user_id, sem precisar
// de GROUP BY (SQLite não tem "latest per group" nativo mais simples que isso).
$planJoin = "LEFT JOIN subscriptions sub ON sub.id = (
        SELECT s2.id FROM subscriptions s2 WHERE s2.user_id = u.id AND s2.status = 'ACTIVE' ORDER BY s2.created_at DESC LIMIT 1
    ) LEFT JOIN plans p ON p.id = sub.plan_id";

if ($q) {
    $stmt = $pdo->prepare("SELECT u.*, a.name AS agency_name, sub.id AS subscription_id, p.id AS current_plan_id, p.name AS current_plan_name
        FROM users u LEFT JOIN agencies a ON a.id = u.agency_id $planJoin
        WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? ORDER BY u.created_at DESC LIMIT 100");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT u.*, a.name AS agency_name, sub.id AS subscription_id, p.id AS current_plan_id, p.name AS current_plan_name
        FROM users u LEFT JOIN agencies a ON a.id = u.agency_id $planJoin ORDER BY u.created_at DESC LIMIT 100");
}
$users = $stmt->fetchAll();
$roles = ['USER', 'ADVERTISER', 'OWNER', 'AGENT', 'AGENCY_ADMIN', 'ADMIN'];
$statuses = ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'PENDING'];
$activePlans = $pdo->query('SELECT id, name FROM plans WHERE active = 1 ORDER BY price')->fetchAll();

$flashSuccess = $_SESSION['admin_success'] ?? null;
$flashError = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('usuarios.csv', [
        'name' => 'Nome', 'email' => 'E-mail', 'agency_name' => 'Imobiliária', 'role' => 'Papel', 'status' => 'Status', 'created_at' => 'Cadastrado em',
    ], array_map(fn($u) => [
        'name' => $u['first_name'] . ' ' . $u['last_name'], 'email' => $u['email'], 'agency_name' => $u['agency_name'],
        'role' => $u['role'], 'status' => $u['status'], 'created_at' => $u['created_at'],
    ], $users));
}

$pageTitle = 'Usuários (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Usuários (<?= count($users) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if ($flashSuccess): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($flashSuccess) ?></p><?php endif; ?>
      <?php if ($flashError): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($flashError) ?></p><?php endif; ?>
      <form class="mb-4 max-w-sm"><input name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></form>
      <?php if (empty($users)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum usuário encontrado.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($users as $u): ?>
            <div class="rounded-2xl border border-brand-border bg-white p-4">
              <div class="mb-3 flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-primary text-sm font-semibold text-white">
                  <?php if (!empty($u['avatar_url'])): ?>
                    <img src="<?= e($u['avatar_url']) ?>" class="h-full w-full object-cover" alt="">
                  <?php else: ?>
                    <?= e(mb_strtoupper(mb_substr($u['first_name'], 0, 1))) ?>
                  <?php endif; ?>
                </span>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-brand-text"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></p>
                  <p class="truncate text-xs text-brand-text-secondary"><?= e($u['email']) ?></p>
                </div>
              </div>
              <p class="mb-3 text-xs text-brand-text-secondary">Imobiliária: <?= e($u['agency_name'] ?? '—') ?></p>
              <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="mb-3 flex flex-wrap gap-2" onchange="this.submit()">
                <?= csrf_field() ?><input type="hidden" name="do" value="update_user"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                <select name="role" class="rounded-lg border border-brand-border px-2 py-1.5 text-xs"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
                <select name="status" class="rounded-lg border border-brand-border px-2 py-1.5 text-xs"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
              </form>
              <div class="border-t border-brand-border pt-3">
                <?php if ($u['current_plan_name']): ?>
                  <p class="mb-2 text-xs text-brand-text-secondary">Plano: <span class="font-semibold text-brand-green-hover"><?= e($u['current_plan_name']) ?></span> (ativo)</p>
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" onsubmit="return confirm('Desativar o plano <?= e(addslashes($u['current_plan_name'])) ?> deste usuário? Ele volta ao plano grátis.');">
                    <?= csrf_field() ?><input type="hidden" name="do" value="set_user_plan"><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="planId" value="">
                    <button type="submit" class="w-full rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">Desativar plano</button>
                  </form>
                <?php elseif (empty($activePlans)): ?>
                  <p class="text-xs text-brand-text-secondary">Plano: grátis · nenhum plano ativo cadastrado</p>
                <?php else: ?>
                  <p class="mb-2 text-xs text-brand-text-secondary">Plano: grátis</p>
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="flex flex-wrap gap-2">
                    <?= csrf_field() ?><input type="hidden" name="do" value="set_user_plan"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <select name="planId" class="min-w-0 flex-1 rounded-lg border border-brand-border px-2 py-1.5 text-xs"><?php foreach ($activePlans as $pl): ?><option value="<?= $pl['id'] ?>"><?= e($pl['name']) ?></option><?php endforeach; ?></select>
                    <button type="submit" class="rounded-full bg-brand-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-primary-hover">Ativar</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('usuarios'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
