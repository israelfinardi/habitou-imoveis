<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$q = trim($_GET['q'] ?? '');
$pdo = db();
if ($q) {
    $stmt = $pdo->prepare('SELECT u.*, a.name AS agency_name FROM users u LEFT JOIN agencies a ON a.id = u.agency_id
        WHERE u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? ORDER BY u.created_at DESC LIMIT 100');
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query('SELECT u.*, a.name AS agency_name FROM users u LEFT JOIN agencies a ON a.id = u.agency_id ORDER BY u.created_at DESC LIMIT 100');
}
$users = $stmt->fetchAll();
$roles = ['USER', 'ADVERTISER', 'OWNER', 'AGENT', 'AGENCY_ADMIN', 'ADMIN'];
$statuses = ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'PENDING'];

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
      <form class="mb-4 max-w-sm"><input name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></form>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
            <tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">E-mail</th><th class="px-4 py-3">Imobiliária</th><th class="px-4 py-3">Papel / Status</th></tr>
          </thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="px-4 py-3"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= e($u['email']) ?></td>
                <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= e($u['agency_name'] ?? '—') ?></td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="flex items-center gap-2" onchange="this.submit()">
                    <?= csrf_field() ?><input type="hidden" name="do" value="update_user"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <select name="role" class="rounded-lg border border-brand-border px-2 py-1 text-xs"><?php foreach ($roles as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
                    <select name="status" class="rounded-lg border border-brand-border px-2 py-1 text-xs"><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <aside><?php render_admin_nav('usuarios'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
