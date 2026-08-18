<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$user = require_login();
if (!in_array($user['role'], ['AGENCY_ADMIN', 'AGENT', 'ADMIN'], true)) {
    http_response_code(403);
    exit('Acesso negado.');
}

$pdo = db();
if ($user['role'] === 'ADMIN') {
    $feeds = $pdo->query('SELECT f.*, a.name AS agency_name, (SELECT COUNT(*) FROM properties p WHERE p.source_feed_id = f.id) AS property_count FROM feeds f JOIN agencies a ON a.id = f.agency_id ORDER BY f.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT f.*, a.name AS agency_name, (SELECT COUNT(*) FROM properties p WHERE p.source_feed_id = f.id) AS property_count FROM feeds f JOIN agencies a ON a.id = f.agency_id WHERE f.agency_id = ? ORDER BY f.created_at DESC');
    $stmt->execute([$user['agency_id']]);
    $feeds = $stmt->fetchAll();
}

$pageTitle = 'Feeds VRSync';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-2 text-2xl font-bold">Feeds VRSync</h1>
      <?php if (!empty($_GET['bemvindo'])): ?>
        <p class="mb-6 rounded-lg bg-brand-bg-subtle px-4 py-3 text-sm text-brand-text-secondary">
          Cadastro de imobiliária recebido! Sua conta já está ativa e você pode anunciar imóveis e configurar feeds VRSync.
          O perfil público da sua imobiliária fica em análise e é liberado assim que o administrador do site aprovar o cadastro.
        </p>
      <?php endif; ?>

      <?php if ($user['agency_id']): ?>
      <div class="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Novo feed</h2>
        <form method="post" action="<?= base_url('actions/feed_action.php') ?>" class="flex flex-wrap items-end gap-3">
          <?= csrf_field() ?><input type="hidden" name="do" value="create">
          <div class="flex-1 min-w-[160px]"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">Nome do feed</label><input name="name" required class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="flex-[2] min-w-[220px]"><label class="mb-1 block text-xs font-medium text-brand-text-secondary">URL do feed (XML)</label><input name="url" type="url" required placeholder="https://" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
          <div class="min-w-[140px]">
            <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Frequência</label>
            <select name="frequency_minutes" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
              <option value="60">A cada hora</option>
              <option value="360">A cada 6 horas</option>
              <option value="1440" selected>Diariamente</option>
              <option value="10080">Semanalmente</option>
            </select>
          </div>
          <button type="submit" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Adicionar feed</button>
        </form>
      </div>
      <?php endif; ?>

      <?php if (empty($feeds)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum feed cadastrado.</div>
      <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Última sinc.</th><th class="px-4 py-3">Resultado</th><th class="px-4 py-3">Imóveis</th></tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <?php foreach ($feeds as $f): ?>
                <tr>
                  <td class="px-4 py-3"><a href="<?= base_url('imobiliaria/feed.php?id=' . $f['id']) ?>" class="font-medium hover:text-brand-primary"><?= e($f['name']) ?></a><p class="text-xs text-brand-text-secondary"><?= e($f['agency_name']) ?></p></td>
                  <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium <?= $f['status'] === 'ACTIVE' ? 'bg-brand-green/10 text-brand-green-hover' : 'bg-brand-bg-subtle text-brand-text-secondary' ?>"><?= $f['status'] === 'ACTIVE' ? 'Ativo' : 'Inativo' ?></span></td>
                  <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= $f['last_sync_at'] ? format_date($f['last_sync_at']) : 'Nunca' ?></td>
                  <td class="px-4 py-3 text-xs"><?= e($f['last_run_status'] ?? '—') ?></td>
                  <td class="px-4 py-3 text-xs"><?= $f['property_count'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('feeds'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
