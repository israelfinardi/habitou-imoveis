<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';

$user = require_role(['ADMIN']);
$plans = db()->query('SELECT * FROM plans ORDER BY price')->fetchAll();

$pageTitle = 'Planos (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside><?php render_admin_nav('planos'); ?></aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Planos</h1>
      <div class="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Novo plano</h2>
        <form method="post" action="<?= base_url('actions/admin_action.php') ?>" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <?= csrf_field() ?><input type="hidden" name="do" value="create_plan">
          <input name="name" required placeholder="Nome" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          <input name="price" required type="number" step="0.01" placeholder="Preço mensal" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          <input name="maxListings" type="number" placeholder="Limite de anúncios (vazio = ilimitado)" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          <input name="description" placeholder="Descrição" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
          <textarea name="features" placeholder="Um recurso por linha" rows="3" class="sm:col-span-2 rounded-lg border border-brand-border px-3 py-2 text-sm"></textarea>
          <button type="submit" class="sm:col-span-2 rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar plano</button>
        </form>
      </div>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary"><tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Preço</th><th class="px-4 py-3">Limite</th><th class="px-4 py-3">Status</th></tr></thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($plans as $p): ?>
              <tr>
                <td class="px-4 py-3 font-medium"><?= e($p['name']) ?></td>
                <td class="px-4 py-3 text-xs"><?= format_currency_brl($p['price']) ?></td>
                <td class="px-4 py-3 text-xs"><?= $p['max_listings'] ?? 'Ilimitado' ?></td>
                <td class="px-4 py-3">
                  <form method="post" action="<?= base_url('actions/admin_action.php') ?>">
                    <?= csrf_field() ?><input type="hidden" name="do" value="toggle_plan"><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="active" value="<?= $p['active'] ? 0 : 1 ?>">
                    <button class="rounded-full px-3 py-1 text-xs font-medium <?= $p['active'] ? 'bg-brand-green/10 text-brand-green-hover' : 'bg-brand-bg-subtle text-brand-text-secondary' ?>"><?= $p['active'] ? 'Ativo' : 'Inativo' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
