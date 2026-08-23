<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

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
if (!can_manage_contract($user, $contract)) {
    http_response_code(403);
    exit('Você não pode editar este contrato.');
}
if (in_array($contract['status'], ['FINISHED', 'CANCELED'], true)) {
    redirect(base_url('contrato.php?id=' . $id));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        update_contract($id, $user, [
            'value' => $_POST['value'] ?: null,
            'startDate' => $_POST['startDate'] ?: null,
            'endDate' => $_POST['endDate'] ?: null,
            'body' => $_POST['body'] ?? '',
            'clientEmail' => $_POST['clientEmail'] ?: null,
            'clientDocument' => $_POST['clientDocument'] ?: null,
        ]);
        redirect(base_url('contrato.php?id=' . $id));
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Recarregar um modelo (opcional): mesmo padrão GET+onchange do contrato-novo.php.
$templates = list_contract_templates_for_user($user, $contract['type']);
$selectedTemplateId = (int) ($_GET['template_id'] ?? 0);
$body = $contract['body'] ?? '';
if ($selectedTemplateId) {
    $template = get_contract_template($selectedTemplateId, $user);
    if ($template) {
        $property = get_property_by_id((int) $contract['property_id']);
        $body = render_contract_body($template['body'], $property, $contract, $contract['client']);
    }
}

$pageTitle = 'Editar contrato';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
  <a href="<?= base_url('contrato.php?id=' . $id) ?>" class="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para o contrato</a>
  <h1 class="mb-1 text-2xl font-bold">Editar contrato de <?= e(CONTRACT_TYPE_LABEL[$contract['type']]) ?></h1>
  <p class="mb-6 text-sm text-brand-text-secondary"><?= e($contract['property_title']) ?></p>
  <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

  <?php if ($templates): ?>
    <form method="get" class="mb-4">
      <label class="mb-1 block text-sm font-medium">Recarregar texto a partir de um modelo</label>
      <select name="template_id" onchange="this.form.submit()" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <option value="">Manter texto atual</option>
        <?php foreach ($templates as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= $selectedTemplateId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium">E-mail do cliente</label>
        <input name="clientEmail" type="email" value="<?= e($contract['client']['email'] ?? '') ?>" placeholder="cliente@email.com" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-brand-text-secondary">Deixe em branco pra manter o cliente atual.</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium">CPF/RG do cliente</label>
        <input name="clientDocument" value="<?= e($contract['client_document'] ?? '') ?>" placeholder="Opcional" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium">Valor (R$)</label>
      <input name="value" type="number" step="0.01" value="<?= e((string) ($contract['value'] ?? '')) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="mb-1 block text-sm font-medium">Data de início</label><input name="startDate" type="date" value="<?= e($contract['start_date'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div><label class="mb-1 block text-sm font-medium">Data de término</label><input name="endDate" type="date" value="<?= e($contract['end_date'] ?? '') ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium">Texto do contrato</label>
      <textarea name="body" rows="14" class="w-full rounded-lg border border-brand-border px-3 py-2 font-mono text-xs leading-relaxed"><?= e($body) ?></textarea>
    </div>
    <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Salvar alterações</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
