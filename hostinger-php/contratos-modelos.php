<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/contract_service.php';

$user = require_login();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $do = $_POST['do'] ?? '';
    try {
        if ($do === 'create_template') {
            create_contract_template($user, [
                'name' => $_POST['name'] ?? '', 'transactionType' => $_POST['transactionType'] ?? 'SALE',
                'body' => $_POST['body'] ?? '', 'isGlobal' => !empty($_POST['isGlobal']), 'scopeAgency' => !empty($_POST['scopeAgency']),
            ]);
            $success = 'Modelo criado com sucesso.';
        } elseif ($do === 'update_template') {
            update_contract_template((int) $_POST['templateId'], $user, [
                'name' => $_POST['name'] ?? '', 'transactionType' => $_POST['transactionType'] ?? 'SALE',
                'body' => $_POST['body'] ?? '', 'isGlobal' => !empty($_POST['isGlobal']),
            ]);
            $success = 'Modelo atualizado com sucesso.';
        } elseif ($do === 'toggle_active') {
            set_contract_template_active((int) $_POST['templateId'], $user, !empty($_POST['activate']));
            $success = !empty($_POST['activate']) ? 'Modelo ativado.' : 'Modelo desativado.';
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$editingTemplate = null;
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId) {
    $editingTemplate = get_contract_template($editId, $user);
    if (!$editingTemplate || !can_manage_contract_template($user, $editingTemplate)) {
        $editingTemplate = null;
    }
}

$templates = list_contract_templates_for_user($user, null, true);
$canScopeAgency = !empty($user['agency_id']) && in_array($user['role'], AGENCY_ROLES, true);
$isAdmin = $user['role'] === 'ADMIN';

$pageTitle = 'Modelos de contrato';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-1 text-2xl font-bold">Modelos de contrato</h1>
      <p class="mb-6 text-sm text-brand-text-secondary">Redija modelos de compra e venda ou locação com campos que se preenchem sozinhos com os dados do imóvel e do cliente na hora de gerar o contrato.</p>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

      <div class="mb-6 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold"><?= $editingTemplate ? 'Editar modelo' : 'Novo modelo' ?></h2>
        <form method="post" class="space-y-3">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="<?= $editingTemplate ? 'update_template' : 'create_template' ?>">
          <?php if ($editingTemplate): ?><input type="hidden" name="templateId" value="<?= (int) $editingTemplate['id'] ?>"><?php endif; ?>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-[2fr_1fr]">
            <div>
              <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Nome do modelo</label>
              <input name="name" required value="<?= e($editingTemplate['name'] ?? '') ?>" placeholder="Ex.: Contrato padrão de venda" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Tipo de transação</label>
              <select name="transactionType" class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
                <?php foreach (CONTRACT_TYPE_LABEL as $val => $label): ?>
                  <option value="<?= e($val) ?>" <?= ($editingTemplate['transaction_type'] ?? 'SALE') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-brand-text-secondary">Texto do contrato</label>
            <textarea name="body" required rows="12" class="w-full rounded-lg border border-brand-border px-3 py-2 font-mono text-xs leading-relaxed" placeholder="Pelo presente instrumento particular, {{corretora_nome}}, representada por {{corretor_nome}}, e {{cliente_nome}}, portador(a) do documento {{cliente_documento}}, firmam o presente contrato de {{contrato_tipo}} referente ao imóvel {{imovel_titulo}}, localizado em {{imovel_endereco}}, {{imovel_bairro}}, {{imovel_cidade}}, pelo valor de {{contrato_valor}}..."><?= e($editingTemplate['body'] ?? '') ?></textarea>
          </div>
          <details class="rounded-lg bg-brand-bg-subtle p-3 text-xs">
            <summary class="cursor-pointer font-semibold text-brand-text">Campos disponíveis (clique para ver)</summary>
            <div class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
              <?php foreach (CONTRACT_PLACEHOLDER_HELP as $token => $desc): ?>
                <p><code class="rounded bg-white px-1 py-0.5 text-[11px] text-brand-primary"><?= e($token) ?></code> <span class="text-brand-text-secondary"><?= e($desc) ?></span></p>
              <?php endforeach; ?>
            </div>
          </details>
          <?php if ($isAdmin): ?>
            <label class="flex items-center gap-2 text-xs font-medium text-brand-text-secondary">
              <input type="checkbox" name="isGlobal" value="1" <?= !empty($editingTemplate['is_global']) ? 'checked' : '' ?>>
              Disponível para todo o site (modelo padrão da Habitou)
            </label>
          <?php elseif ($canScopeAgency && !$editingTemplate): ?>
            <label class="flex items-center gap-2 text-xs font-medium text-brand-text-secondary">
              <input type="checkbox" name="scopeAgency" value="1" checked>
              Disponível para toda a imobiliária (não só pra mim)
            </label>
          <?php endif; ?>
          <div class="flex items-center gap-3">
            <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover"><?= $editingTemplate ? 'Salvar alterações' : 'Criar modelo' ?></button>
            <?php if ($editingTemplate): ?><a href="<?= base_url('contratos-modelos.php') ?>" class="text-sm text-brand-text-secondary hover:underline">Cancelar</a><?php endif; ?>
          </div>
        </form>
      </div>

      <?php if (empty($templates)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhum modelo de contrato ainda.</div>
      <?php else: ?>
        <div class="<?= CARD_GRID_CLASS ?>">
          <?php foreach ($templates as $t): ?>
            <div class="rounded-xl border border-brand-border bg-white p-4">
              <div class="mb-2 flex flex-wrap items-center gap-1.5">
                <span class="rounded-full bg-brand-bg-subtle px-2 py-0.5 text-[11px] font-medium"><?= e(CONTRACT_TYPE_LABEL[$t['transaction_type']] ?? $t['transaction_type']) ?></span>
                <?php if ($t['is_global']): ?><span class="rounded-full bg-brand-primary/10 px-2 py-0.5 text-[11px] font-medium text-brand-primary">Site inteiro</span>
                <?php elseif ($t['agency_id']): ?><span class="rounded-full bg-brand-primary/10 px-2 py-0.5 text-[11px] font-medium text-brand-primary">Imobiliária</span>
                <?php else: ?><span class="rounded-full bg-brand-bg-subtle px-2 py-0.5 text-[11px] font-medium text-brand-text-secondary">Pessoal</span><?php endif; ?>
                <?php if (!$t['is_active']): ?><span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700">Desativado</span><?php endif; ?>
              </div>
              <p class="mb-1 font-semibold"><?= e($t['name']) ?></p>
              <p class="mb-3 line-clamp-3 text-xs text-brand-text-secondary"><?= e(mb_substr($t['body'], 0, 160)) ?><?= mb_strlen($t['body']) > 160 ? '…' : '' ?></p>
              <?php if (can_manage_contract_template($user, $t)): ?>
                <div class="flex items-center gap-3 text-xs font-semibold">
                  <a href="<?= base_url('contratos-modelos.php?edit=' . $t['id']) ?>" class="text-brand-primary hover:underline">Editar</a>
                  <form method="post" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="do" value="toggle_active"><input type="hidden" name="templateId" value="<?= (int) $t['id'] ?>">
                    <input type="hidden" name="activate" value="<?= $t['is_active'] ? '0' : '1' ?>">
                    <button class="<?= $t['is_active'] ? 'text-red-600' : 'text-brand-green' ?> hover:underline"><?= $t['is_active'] ? 'Desativar' : 'Ativar' ?></button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('contratos_modelos'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
