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
        ]);
        redirect(base_url('contrato.php?id=' . $id));
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Novo contrato';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">
  <h1 class="mb-1 text-2xl font-bold">Novo contrato</h1>
  <p class="mb-6 text-sm text-brand-text-secondary"><?= e($property['title']) ?></p>
  <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="mb-1 block text-sm font-medium">Valor (R$)</label>
      <input name="value" type="number" step="0.01" value="<?= e((string) ($property['price_sale'] ?? $property['price_rent'] ?? '')) ?>" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div><label class="mb-1 block text-sm font-medium">Data de início</label><input name="startDate" type="date" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
      <div><label class="mb-1 block text-sm font-medium">Data de término</label><input name="endDate" type="date" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></div>
    </div>
    <div><label class="mb-1 block text-sm font-medium">Observação</label><textarea name="note" rows="3" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm"></textarea></div>
    <button type="submit" class="rounded-full bg-brand-primary px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar contrato</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
