<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_once __DIR__ . '/../includes/contract_service.php';

$user = require_role(['ADMIN']);
$contracts = list_contracts_for_user($user);
$statusLabel = ['DRAFT' => 'Rascunho', 'ACTIVE' => 'Ativo', 'FINISHED' => 'Concluído', 'CANCELED' => 'Cancelado'];

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('contratos.csv', [
        'property_title' => 'Imóvel', 'type' => 'Tipo', 'value' => 'Valor', 'status' => 'Status',
    ], array_map(fn($c) => [
        'property_title' => $c['property_title'], 'type' => $c['type'] === 'SALE' ? 'Venda' : 'Aluguel',
        'value' => $c['value'], 'status' => $statusLabel[$c['status']] ?? $c['status'],
    ], $contracts));
}

$pageTitle = 'Contratos (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Contratos (<?= count($contracts) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if (empty($contracts)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum contrato encontrado.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($contracts as $c): ?>
            <a href="<?= base_url('contrato.php?id=' . $c['id']) ?>" class="block rounded-2xl border border-brand-border bg-white p-4 hover:border-brand-primary">
              <p class="mb-2 truncate text-sm font-semibold text-brand-text"><?= e($c['property_title']) ?></p>
              <p class="mb-1 text-xs text-brand-text-secondary"><?= $c['type'] === 'SALE' ? 'Venda' : 'Aluguel' ?></p>
              <p class="mb-3 text-sm font-semibold text-brand-text"><?= $c['value'] ? format_currency_brl($c['value']) : '—' ?></p>
              <span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium"><?= e($statusLabel[$c['status']]) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('contratos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
