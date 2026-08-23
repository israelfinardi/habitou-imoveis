<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? 0);

$error = $_SESSION['contract_error'] ?? null;
$flashSuccess = $_SESSION['contract_success'] ?? null;
unset($_SESSION['contract_error'], $_SESSION['contract_success']);

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

$canManage = can_manage_contract($user, $contract);
$addressParts = array_filter([$contract['street'] ?? null, $contract['number'] ?? null, $contract['complement'] ?? null]);

$pageTitle = 'Contrato #' . $contract['id'];
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main class="max-w-3xl">
      <a href="<?= base_url('contratos.php') ?>" class="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para contratos</a>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
      <?php if ($flashSuccess): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($flashSuccess) ?></p><?php endif; ?>

      <div class="rounded-xl border border-brand-border bg-white p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-bold"><?= e($contract['property_title']) ?></h1>
            <p class="text-xs text-brand-text-secondary"><?= e(CONTRACT_TYPE_LABEL[$contract['type']] ?? $contract['type']) ?> · <?= e($contract['code'] ?? '') ?></p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full bg-brand-bg-subtle px-3 py-1 text-xs font-semibold"><?= e(CONTRACT_STATUS_LABEL[$contract['status']]) ?></span>
            <a href="<?= base_url('actions/download_contract_pdf.php?id=' . $contract['id']) ?>" class="rounded-full border border-brand-border px-3 py-1.5 text-xs font-semibold hover:border-brand-primary hover:text-brand-primary">Baixar PDF</a>
            <?php if ($canManage): ?>
              <a href="<?= base_url('contrato-editar.php?id=' . $contract['id']) ?>" class="rounded-full border border-brand-border px-3 py-1.5 text-xs font-semibold hover:border-brand-primary hover:text-brand-primary">Editar</a>
            <?php endif; ?>
          </div>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm">
          <div><dt class="text-brand-text-secondary">Valor</dt><dd><?= $contract['value'] ? format_currency_brl($contract['value']) : '—' ?></dd></div>
          <div><dt class="text-brand-text-secondary">Endereço</dt><dd><?= $addressParts ? e(implode(', ', $addressParts)) : '—' ?></dd></div>
          <div><dt class="text-brand-text-secondary">Início</dt><dd><?= $contract['start_date'] ? format_date($contract['start_date']) : '—' ?></dd></div>
          <div><dt class="text-brand-text-secondary">Término</dt><dd><?= $contract['end_date'] ? format_date($contract['end_date']) : '—' ?></dd></div>
          <div><dt class="text-brand-text-secondary">Cliente</dt><dd><?= $contract['client'] ? e(trim($contract['client']['first_name'] . ' ' . $contract['client']['last_name'])) . ' · ' . e($contract['client']['email']) : '—' ?></dd></div>
          <div><dt class="text-brand-text-secondary">Documento do cliente</dt><dd><?= e($contract['client_document'] ?? '') ?: '—' ?></dd></div>
        </dl>

        <?php if (trim((string) ($contract['body'] ?? '')) !== ''): ?>
          <div class="mt-6 border-t border-brand-border pt-4">
            <h2 class="mb-2 text-sm font-semibold">Texto do contrato</h2>
            <div class="max-h-64 overflow-y-auto whitespace-pre-wrap rounded-lg bg-brand-bg-subtle p-4 text-xs leading-relaxed text-brand-text-secondary"><?= e($contract['body']) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($canManage): ?>
          <div class="mt-6 border-t border-brand-border pt-4">
            <h2 class="mb-3 text-sm font-semibold">Status do contrato</h2>
            <div class="mb-3 flex flex-wrap gap-2">
              <?php if ($contract['status'] !== 'ACTIVE'): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="status" value="ACTIVE">
                  <button class="rounded-full bg-brand-green px-4 py-2 text-xs font-semibold text-white hover:bg-brand-green-hover">Ativar contrato</button>
                </form>
              <?php endif; ?>
              <?php if ($contract['status'] !== 'CANCELED'): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="status" value="CANCELED">
                  <button class="rounded-full border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Desativar contrato</button>
                </form>
              <?php endif; ?>
              <?php if ($contract['status'] !== 'FINISHED'): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="status" value="FINISHED">
                  <button class="rounded-full border border-brand-border px-4 py-2 text-xs font-semibold hover:border-brand-primary hover:text-brand-primary">Marcar como concluído</button>
                </form>
              <?php endif; ?>
            </div>
            <form method="post" class="flex flex-wrap items-end gap-3">
              <?= csrf_field() ?>
              <div>
                <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Outro status</label>
                <select name="status" class="rounded-lg border border-brand-border px-3 py-2 text-sm">
                  <?php foreach (CONTRACT_STATUS_LABEL as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $contract['status'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="min-w-[200px] flex-1">
                <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Observação</label>
                <input name="note" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" placeholder="Opcional">
              </div>
              <button type="submit" class="rounded-full border border-brand-border px-4 py-2 text-sm font-semibold hover:border-brand-primary hover:text-brand-primary">Atualizar status</button>
            </form>
          </div>
        <?php endif; ?>

        <div class="mt-6 border-t border-brand-border pt-4">
          <h2 class="mb-3 text-sm font-semibold">Documento assinado</h2>
          <?php if (!empty($contract['signed_document_url'])): ?>
            <a href="<?= e($contract['signed_document_url']) ?>" target="_blank" rel="noopener" class="mb-3 flex items-center gap-2 rounded-lg bg-brand-bg-subtle px-4 py-3 text-sm font-medium text-brand-primary hover:underline">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
              Ver contrato assinado <span class="text-xs font-normal text-brand-text-secondary">(enviado em <?= format_date($contract['signed_document_uploaded_at']) ?>)</span>
            </a>
          <?php else: ?>
            <p class="mb-3 text-sm text-brand-text-secondary">Nenhum documento assinado enviado ainda. Baixe o PDF acima, assine (fora da plataforma) e envie aqui.</p>
          <?php endif; ?>
          <form method="post" action="<?= base_url('actions/upload_contract_document.php') ?>" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="contractId" value="<?= (int) $contract['id'] ?>">
            <input type="file" name="document" accept="application/pdf" required class="text-sm">
            <button type="submit" class="rounded-full bg-brand-primary px-4 py-2 text-xs font-semibold text-white hover:bg-brand-primary-hover">Enviar PDF assinado</button>
          </form>
        </div>

        <div class="mt-6 border-t border-brand-border pt-4">
          <h2 class="mb-3 text-sm font-semibold">Histórico</h2>
          <ul class="space-y-2">
            <?php foreach ($contract['history'] as $h): ?>
              <li class="rounded-lg bg-brand-bg-subtle p-3 text-xs">
                <span class="font-semibold"><?= e(CONTRACT_STATUS_LABEL[$h['status']] ?? $h['status']) ?></span>
                <span class="ml-2 text-brand-text-secondary"><?= format_date($h['created_at']) ?></span>
                <?php if ($h['note']): ?><p class="mt-1 text-brand-text-secondary"><?= e($h['note']) ?></p><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </main>
    <aside><?php render_account_nav('contratos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
