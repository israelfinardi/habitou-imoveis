<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_once __DIR__ . '/../includes/contract_service.php';

$user = require_role(['ADMIN']);
$contracts = list_contracts_for_user($user);
$statusLabel = ['DRAFT' => 'Rascunho', 'ACTIVE' => 'Ativo', 'FINISHED' => 'Concluído', 'CANCELED' => 'Cancelado'];

$pageTitle = 'Contratos (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-6 text-2xl font-bold">Contratos (<?= count($contracts) ?>)</h1>
      <div class="overflow-hidden rounded-xl border border-brand-border">
        <table class="w-full text-sm">
          <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary"><tr><th class="px-4 py-3">Imóvel</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Valor</th><th class="px-4 py-3">Status</th></tr></thead>
          <tbody class="divide-y divide-brand-border">
            <?php foreach ($contracts as $c): ?>
              <tr>
                <td class="px-4 py-3"><a href="<?= base_url('contrato.php?id=' . $c['id']) ?>" class="font-medium hover:text-brand-primary"><?= e($c['property_title']) ?></a></td>
                <td class="px-4 py-3 text-xs"><?= $c['type'] === 'SALE' ? 'Venda' : 'Aluguel' ?></td>
                <td class="px-4 py-3 text-xs"><?= $c['value'] ? format_currency_brl($c['value']) : '—' ?></td>
                <td class="px-4 py-3 text-xs"><?= e($statusLabel[$c['status']]) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <aside><?php render_admin_nav('contratos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
