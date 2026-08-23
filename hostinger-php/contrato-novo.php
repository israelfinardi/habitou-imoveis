<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();
$propertyId = (int) ($_GET['imovel_id'] ?? $_POST['propertyId'] ?? 0);
$property = get_property_by_id($propertyId);
if (!$property || !can_manage_property($user, $property)) {
    http_response_code(404);
    exit('Imóvel não encontrado.');
}
$type = $property['listing_type'] === 'RENT' ? 'RENT' : 'SALE';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = create_contract($user, [
            'propertyId' => $propertyId,
            'value' => $_POST['value'] ?: null,
            'startDate' => $_POST['startDate'] ?: null,
            'endDate' => $_POST['endDate'] ?: null,
            'note' => $_POST['note'] ?: null,
            'templateId' => $_POST['templateId'] ?: null,
            'body' => $_POST['body'] ?? '',
            'clientEmail' => $_POST['clientEmail'] ?: null,
            'clientDocument' => $_POST['clientDocument'] ?: null,
        ]);
        redirect(base_url('contrato.php?id=' . $id));
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

// Seleção de modelo via GET recarrega a página com o texto pré-preenchido
// (troca de <select> com onchange="this.form.submit()") — evita precisar de
// JS extra pra buscar o texto do modelo por fetch.
$templates = list_contract_templates_for_user($user, $type);
$selectedTemplateId = (int) ($_GET['template_id'] ?? 0);
$prefillBody = '';
if ($selectedTemplateId) {
    $template = get_contract_template($selectedTemplateId, $user);
    if ($template) {
        $draftContract = [
            'type' => $type, 'value' => $property['price_sale'] ?? $property['price_rent'] ?? null,
            'advertiser_id' => $property['advertiser_id'], 'owner_id' => $property['owner_id'], 'agency_id' => $property['agency_id'],
        ];
        $prefillBody = render_contract_body($template['body'], $property, $draftContract, null);
    }
}

$pageTitle = 'Novo contrato';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
  <h1 class="mb-1 text-2xl font-bold">Novo contrato de <?= CONTRACT_TYPE_LABEL[$type] ?></h1>
  <p class="mb-6 text-sm text-brand-text-secondary"><?= e($property['title']) ?></p>
  <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

  <?php if ($templates): ?>
    <form method="get" class="mb-4">
      <input type="hidden" name="imovel_id" value="<?= (int) $propertyId ?>">
      <label class="mb-1 block text-sm font-medium">Começar a partir de um modelo</label>
      <select name="template_id" onchange="this.form.submit()" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <option value="">Em branco</option>
        <?php foreach ($templates as $t): ?>
          <option value="<?= (int) $t['id'] ?>" <?= $selectedTemplateId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php else: ?>
    <p class="mb-4 rounded-lg bg-brand-bg-subtle px-3 py-2 text-xs text-brand-text-secondary">Você ainda não tem modelos de <?= mb_strtolower(CONTRACT_TYPE_LABEL[$type], 'UTF-8') ?> cadastrados. <a href="<?= base_url('contratos-modelos.php') ?>" class="font-semibold text-brand-primary hover:underline">Criar um modelo</a> (opcional — você pode escrever o texto direto abaixo).</p>
  <?php endif; ?>

  <form method="post" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="templateId" value="<?= $selectedTemplateId ?: '' ?>">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium">E-mail do cliente</label>
        <input name="clientEmail" type="email" placeholder="cliente@email.com" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-brand-text-secondary">O cliente precisa já ter uma conta no site.</p>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium">CPF/RG do cliente</label>
        <input name="clientDocument" placeholder="Opcional" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
      </div>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium">Valor (R$)</label>
      <input name="value" type="number" step="0.01" value="<?= e((string) ($property['price_sale'] ?? $property['price_rent'] ?? '')) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="mb-1 block text-sm font-medium">Data de início</label><input name="startDate" type="date" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div><label class="mb-1 block text-sm font-medium">Data de término</label><input name="endDate" type="date" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium">Texto do contrato</label>
      <textarea name="body" rows="12" class="w-full rounded-lg border border-brand-border px-3 py-2 font-mono text-xs leading-relaxed" placeholder="Escreva o contrato ou escolha um modelo acima."><?= e($prefillBody) ?></textarea>
      <p class="mt-1 text-xs text-brand-text-secondary">Você pode editar este texto livremente antes de criar o contrato.</p>
    </div>
    <div><label class="mb-1 block text-sm font-medium">Observação interna</label><textarea name="note" rows="2" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm" placeholder="Não aparece no contrato, só no histórico interno"></textarea></div>
    <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar contrato</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
