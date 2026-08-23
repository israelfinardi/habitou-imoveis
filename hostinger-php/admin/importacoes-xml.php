<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_nav.php';
require_once __DIR__ . '/../includes/xml_import_service.php';

$user = require_role(['ADMIN']);
$imports = list_xml_imports_for_user($user);

$flashError = $_SESSION['xml_import_error'] ?? null;
$flashSuccess = $_SESSION['xml_import_success'] ?? null;
unset($_SESSION['xml_import_error'], $_SESSION['xml_import_success']);

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('importacoes-xml.csv', [
        'file' => 'Arquivo', 'user' => 'Usuário', 'status' => 'Status', 'found' => 'Encontrados',
        'created' => 'Criados', 'updated' => 'Atualizados', 'deactivated' => 'Arquivados', 'errors' => 'Erros', 'created_at' => 'Enviado em',
    ], array_map(fn ($i) => [
        'file' => $i['original_filename'], 'user' => trim($i['first_name'] . ' ' . $i['last_name']) . ' <' . $i['user_email'] . '>',
        'status' => XML_IMPORT_STATUS_LABEL[$i['status']] ?? $i['status'], 'found' => $i['total_found'], 'created' => $i['total_created'],
        'updated' => $i['total_updated'], 'deactivated' => $i['total_deactivated'], 'errors' => $i['total_errors'], 'created_at' => $i['created_at'],
    ], $imports));
}

$pageTitle = 'Importações de XML (admin)';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Importações de XML (<?= count($imports) ?>)</h1>
        <?php render_csv_export_button(); ?>
      </div>
      <?php if ($flashSuccess): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($flashSuccess) ?></p><?php endif; ?>
      <?php if ($flashError): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($flashError) ?></p><?php endif; ?>
      <?php if (empty($imports)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhuma importação realizada ainda.</div>
      <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th class="px-4 py-3">Arquivo</th>
                <th class="px-4 py-3">Usuário</th>
                <th class="px-4 py-3">Enviado em</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Encontrados</th>
                <th class="px-4 py-3">Criados</th>
                <th class="px-4 py-3">Atualizados</th>
                <th class="px-4 py-3">Arquivados</th>
                <th class="px-4 py-3">Erros</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <?php foreach ($imports as $imp): ?>
                <tr>
                  <td class="px-4 py-3"><p class="max-w-[200px] truncate font-medium" title="<?= e($imp['original_filename']) ?>"><?= e($imp['original_filename']) ?></p></td>
                  <td class="px-4 py-3 text-xs">
                    <p class="font-medium text-brand-text"><?= e(trim($imp['first_name'] . ' ' . $imp['last_name'])) ?></p>
                    <p class="text-brand-text-secondary"><?= e($imp['user_email']) ?></p>
                  </td>
                  <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= format_date($imp['created_at']) ?></td>
                  <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-1 text-xs font-medium <?= $imp['status'] === 'SUCCESS' ? 'bg-brand-green/10 text-brand-green-hover' : ($imp['status'] === 'ERROR' ? 'bg-red-50 text-red-700' : 'bg-brand-bg-subtle text-brand-text-secondary') ?>">
                      <?= e(XML_IMPORT_STATUS_LABEL[$imp['status']] ?? $imp['status']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_found'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_created'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_updated'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_deactivated'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_errors'] ?></td>
                  <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="<?= base_url('actions/download_xml_import.php?id=' . $imp['id']) ?>" class="text-xs font-semibold text-brand-primary hover:underline">Baixar XML</a>
                    <form method="post" action="<?= base_url('actions/delete_xml_import.php') ?>" class="inline" onsubmit="return confirm('Excluir esta importação e os <?= count_properties_for_xml_import((int) $imp['id']) ?> imóvel(is) criados por ela? Isso não pode ser desfeito.');">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $imp['id'] ?>"><input type="hidden" name="from_admin" value="1">
                      <button type="submit" class="ml-3 text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_admin_nav('importacoes_xml'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
