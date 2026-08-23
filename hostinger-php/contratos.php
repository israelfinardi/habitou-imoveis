<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$contracts = list_contracts_for_user($user);
$statusLabel = CONTRACT_STATUS_LABEL;

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('contratos.csv', [
        'property_title' => 'Imóvel', 'type' => 'Tipo', 'value' => 'Valor', 'status' => 'Status', 'updated_at' => 'Atualizado em',
    ], array_map(fn($c) => [
        'property_title' => $c['property_title'], 'type' => CONTRACT_TYPE_LABEL[$c['type']] ?? $c['type'],
        'value' => $c['value'], 'status' => $statusLabel[$c['status']] ?? $c['status'], 'updated_at' => $c['updated_at'],
    ], $contracts));
}

$pageTitle = 'Contratos';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Contratos</h1>
        <div class="flex items-center gap-3">
          <a href="<?= base_url('contratos-modelos.php') ?>" class="rounded-full border border-brand-border px-4 py-2 text-sm font-semibold hover:border-brand-primary hover:text-brand-primary">Modelos de contrato</a>
          <?php render_csv_export_button(); ?>
        </div>
      </div>
      <?php if (empty($contracts)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum contrato encontrado. Contratos são criados a partir de um imóvel na área do anunciante.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($contracts as $c): ?>
            <a href="<?= base_url('contrato.php?id=' . $c['id']) ?>" class="block rounded-2xl border border-brand-border bg-white p-4 hover:border-brand-primary">
              <p class="mb-2 truncate text-sm font-semibold text-brand-text"><?= e($c['property_title']) ?></p>
              <p class="mb-1 text-xs text-brand-text-secondary"><?= e(CONTRACT_TYPE_LABEL[$c['type']] ?? $c['type']) ?></p>
              <p class="mb-3 text-sm font-semibold text-brand-text"><?= $c['value'] ? format_currency_brl($c['value']) : '—' ?></p>
              <div class="flex items-center justify-between gap-2">
                <span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium"><?= e($statusLabel[$c['status']]) ?></span>
                <?php if (!empty($c['signed_document_url'])): ?>
                  <span class="text-xs font-medium text-brand-green">Assinado</span>
                <?php else: ?>
                  <span class="text-xs text-brand-text-secondary"><?= format_date($c['updated_at']) ?></span>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('contratos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
