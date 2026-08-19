<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
if (!in_array($user['role'], ['AGENCY_ADMIN', 'AGENT', 'ADMIN'], true)) {
    http_response_code(403);
    exit('Acesso negado.');
}

$id = (int) ($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT f.*, a.name AS agency_name FROM feeds f JOIN agencies a ON a.id = f.agency_id WHERE f.id = ?');
$stmt->execute([$id]);
$feed = $stmt->fetch();
if (!$feed || ($user['role'] !== 'ADMIN' && (int) $feed['agency_id'] !== (int) $user['agency_id'])) {
    http_response_code(404);
    exit('Feed não encontrado.');
}

$logsStmt = $pdo->prepare('SELECT * FROM feed_sync_logs WHERE feed_id = ? ORDER BY started_at DESC LIMIT 20');
$logsStmt->execute([$id]);
$logs = $logsStmt->fetchAll();

$pageTitle = 'Detalhe do feed';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <nav class="flex flex-col gap-1">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Imóveis</a>
        <a href="<?= base_url('imobiliaria/feeds.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle">Feeds VRSync</a>
      </nav>
    </aside>
    <main>
      <a href="<?= base_url('imobiliaria/feeds.php') ?>" class="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para feeds</a>
      <div class="mb-6 rounded-xl border border-brand-border bg-white p-5">
        <div class="mb-3 flex items-start justify-between">
          <div><h1 class="text-xl font-bold"><?= e($feed['name']) ?></h1><p class="break-all text-xs text-brand-text-secondary"><?= e(decrypt_value($feed['url'])) ?></p></div>
          <span class="shrink-0 rounded-full px-2 py-1 text-xs font-medium <?= $feed['status'] === 'ACTIVE' ? 'bg-brand-green/10 text-brand-green-hover' : 'bg-brand-bg-subtle text-brand-text-secondary' ?>"><?= $feed['status'] === 'ACTIVE' ? 'Ativo' : 'Inativo' ?></span>
        </div>
        <div class="mb-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
          <div><p class="text-xs text-brand-text-secondary">Frequência</p><p>a cada <?= $feed['frequency_minutes'] ?> min</p></div>
          <div><p class="text-xs text-brand-text-secondary">Última sincronização</p><p><?= $feed['last_sync_at'] ? format_date($feed['last_sync_at']) : 'Nunca' ?></p></div>
          <div><p class="text-xs text-brand-text-secondary">Próxima sincronização</p><p><?= $feed['next_sync_at'] ? format_date($feed['next_sync_at']) : '—' ?></p></div>
          <div><p class="text-xs text-brand-text-secondary">Último resultado</p><p><?= e($feed['last_run_status'] ?? '—') ?></p></div>
        </div>
        <div class="flex flex-wrap gap-2">
          <form method="post" action="<?= base_url('actions/feed_action.php') ?>"><?= csrf_field() ?><input type="hidden" name="do" value="sync_now"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Sincronizar agora</button>
          </form>
          <form method="post" action="<?= base_url('actions/feed_action.php') ?>"><?= csrf_field() ?><input type="hidden" name="do" value="toggle_status"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary"><?= $feed['status'] === 'ACTIVE' ? 'Desativar' : 'Ativar' ?></button>
          </form>
          <form method="post" action="<?= base_url('actions/feed_action.php') ?>" onsubmit="return confirm('Excluir este feed?');"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= $id ?>">
            <button class="rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:border-red-400">Excluir feed</button>
          </form>
        </div>
      </div>

      <h2 class="mb-3 text-lg font-bold">Histórico de sincronizações</h2>
      <?php if (empty($logs)): ?>
        <p class="text-sm text-brand-text-secondary">Nenhuma sincronização executada ainda.</p>
      <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr><th class="px-4 py-2">Início</th><th class="px-4 py-2">Status</th><th class="px-4 py-2">Encontrados</th><th class="px-4 py-2">Criados</th><th class="px-4 py-2">Atualizados</th><th class="px-4 py-2">Sem alteração</th><th class="px-4 py-2">Desativados</th><th class="px-4 py-2">Erros</th></tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <?php foreach ($logs as $log): ?>
                <tr>
                  <td class="px-4 py-2 text-xs"><?= format_date($log['started_at']) ?></td>
                  <td class="px-4 py-2 text-xs"><?= e($log['status']) ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_found'] ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_created'] ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_updated'] ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_unchanged'] ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_deactivated'] ?></td>
                  <td class="px-4 py-2 text-xs"><?= $log['total_errors'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
