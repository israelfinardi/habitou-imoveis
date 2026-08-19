<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$contracts = list_contracts_for_user($user);
$statusLabel = ['DRAFT' => 'Rascunho', 'ACTIVE' => 'Ativo', 'FINISHED' => 'Concluído', 'CANCELED' => 'Cancelado'];

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('contratos.csv', [
        'property_title' => 'Imóvel', 'type' => 'Tipo', 'value' => 'Valor', 'status' => 'Status', 'updated_at' => 'Atualizado em',
    ], array_map(fn($c) => [
        'property_title' => $c['property_title'], 'type' => $c['type'] === 'SALE' ? 'Venda' : 'Aluguel',
        'value' => $c['value'], 'status' => $statusLabel[$c['status']] ?? $c['status'], 'updated_at' => $c['updated_at'],
    ], $contracts));
}

$pageTitle = 'Contratos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Contratos</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if (empty($contracts)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum contrato encontrado. Contratos são criados a partir de um imóvel na área do anunciante.</div>
      <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr><th class="px-4 py-3">Imóvel</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Valor</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Atualizado</th></tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <?php foreach ($contracts as $c): ?>
                <tr>
                  <td class="px-4 py-3"><a href="<?= base_url('contrato.php?id=' . $c['id']) ?>" class="font-medium hover:text-brand-primary"><?= e($c['property_title']) ?></a></td>
                  <td class="px-4 py-3"><?= $c['type'] === 'SALE' ? 'Venda' : 'Aluguel' ?></td>
                  <td class="px-4 py-3"><?= $c['value'] ? format_currency_brl($c['value']) : '—' ?></td>
                  <td class="px-4 py-3"><span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium"><?= e($statusLabel[$c['status']]) ?></span></td>
                  <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= format_date($c['updated_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('contratos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
