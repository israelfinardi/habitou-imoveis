<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        update_contract_status($id, $_POST['status'], $_POST['note'] ?: null, $user);
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

try {
    $contract = get_contract($id, $user);
} catch (\Throwable $e) {
    http_response_code(403);
    exit(e($e->getMessage()));
}
if (!$contract) {
    http_response_code(404);
    exit('Contrato não encontrado.');
}

$statusLabel = ['DRAFT' => 'Rascunho', 'ACTIVE' => 'Ativo', 'FINISHED' => 'Concluído', 'CANCELED' => 'Cancelado'];
$canManage = $user['role'] === 'ADMIN' || $contract['advertiser_id'] == $user['id'] || (!empty($contract['agency_id']) && $contract['agency_id'] == ($user['agency_id'] ?? null));

$pageTitle = 'Detalhe do contrato';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
  <a href="<?= base_url('contratos.php') ?>" class="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para contratos</a>
  <div class="rounded-xl border border-brand-border bg-white p-6">
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-xl font-bold"><?= e($contract['property_title']) ?></h1>
      <span class="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs font-semibold"><?= e($statusLabel[$contract['status']]) ?></span>
    </div>
    <dl class="grid grid-cols-2 gap-4 text-sm">
      <div><dt class="text-brand-text-secondary">Tipo</dt><dd><?= $contract['type'] === 'SALE' ? 'Venda' : 'Aluguel' ?></dd></div>
      <div><dt class="text-brand-text-secondary">Valor</dt><dd><?= $contract['value'] ? format_currency_brl($contract['value']) : '—' ?></dd></div>
      <div><dt class="text-brand-text-secondary">Início</dt><dd><?= $contract['start_date'] ? format_date($contract['start_date']) : '—' ?></dd></div>
      <div><dt class="text-brand-text-secondary">Término</dt><dd><?= $contract['end_date'] ? format_date($contract['end_date']) : '—' ?></dd></div>
    </dl>

    <?php if ($canManage): ?>
      <div class="mt-6 border-t border-brand-border pt-4">
        <?php if ($error): ?><p class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="flex flex-wrap items-end gap-3">
          <?= csrf_field() ?>
          <div>
            <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Novo status</label>
            <select name="status" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
              <?php foreach ($statusLabel as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= $contract['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="min-w-[200px] flex-1">
            <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Observação</label>
            <input name="note" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" placeholder="Opcional">
          </div>
          <button type="submit" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Atualizar status</button>
        </form>
      </div>
    <?php endif; ?>

    <div class="mt-6 border-t border-brand-border pt-4">
      <h2 class="mb-3 text-sm font-semibold">Histórico</h2>
      <ul class="space-y-2">
        <?php foreach ($contract['history'] as $h): ?>
          <li class="rounded-lg bg-brand-bg-subtle p-3 text-xs">
            <span class="font-semibold"><?= e($statusLabel[$h['status']]) ?></span>
            <span class="ml-2 text-brand-text-secondary"><?= format_date($h['created_at']) ?></span>
            <?php if ($h['note']): ?><p class="mt-1 text-brand-text-secondary"><?= e($h['note']) ?></p><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
