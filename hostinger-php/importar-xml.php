<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/xml_import_service.php';

$user = require_login();

$error = $_SESSION['xml_import_error'] ?? null;
$success = $_SESSION['xml_import_success'] ?? null;
unset($_SESSION['xml_import_error'], $_SESSION['xml_import_success']);

$imports = list_xml_imports_for_user($user);

$pageTitle = 'Importar XML de imóveis';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <h1 class="mb-1 text-2xl font-bold">Importar XML de imóveis</h1>
      <p class="mb-6 max-w-2xl text-sm text-brand-text-secondary">
        Envie o arquivo XML exportado pelo seu CRM imobiliário (padrão VRSync — compatível com a maioria dos sistemas do mercado, como Vista, Union Softwares, ImobiBrasil, Sublime, Kenlo e outros) e o site cria ou atualiza seus anúncios automaticamente: título, descrição, tipo, preço, endereço, características e fotos (baixadas e hospedadas aqui, sem depender do site de origem).
      </p>
      <?php if ($success): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover"><?= e($success) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>

      <div class="mb-8 rounded-xl border border-brand-border bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold">Novo arquivo</h2>
        <form method="post" action="<?= base_url('actions/upload_xml_import.php') ?>" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
          <?= csrf_field() ?>
          <input type="file" name="xmlFile" accept=".xml,text/xml,application/xml" required class="text-sm">
          <button type="submit" class="rounded-full bg-brand-primary px-5 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Importar</button>
        </form>
        <p class="mt-2 text-xs text-brand-text-secondary">Arquivos grandes podem levar alguns minutos, principalmente se tiverem muitas fotos. Reenviar o mesmo arquivo atualiza os imóveis já importados (pelo código do imóvel) em vez de duplicar; imóveis que saírem do arquivo são arquivados.</p>
      </div>

      <?php if (empty($imports)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Nenhuma importação realizada ainda.</div>
      <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr>
                <th class="px-4 py-3">Arquivo</th>
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
                  <td class="px-4 py-3">
                    <p class="max-w-[220px] truncate font-medium" title="<?= e($imp['original_filename']) ?>"><?= e($imp['original_filename']) ?></p>
                    <?php if ($user['role'] === 'ADMIN'): ?><p class="text-xs text-brand-text-secondary"><?= e(trim($imp['first_name'] . ' ' . $imp['last_name'])) ?></p><?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-xs text-brand-text-secondary"><?= format_date($imp['created_at']) ?></td>
                  <td class="px-4 py-3">
                    <span class="rounded-full px-2 py-1 text-xs font-medium <?= $imp['status'] === 'SUCCESS' ? 'bg-brand-green/10 text-brand-green-hover' : ($imp['status'] === 'ERROR' ? 'bg-red-50 text-red-700' : 'bg-brand-bg-subtle text-brand-text-secondary') ?>">
                      <?= e(XML_IMPORT_STATUS_LABEL[$imp['status']] ?? $imp['status']) ?>
                    </span>
                    <?php if ($imp['status'] === 'ERROR' && $imp['error_summary']): ?><p class="mt-1 max-w-[220px] text-xs text-red-700"><?= e($imp['error_summary']) ?></p><?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_found'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_created'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_updated'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_deactivated'] ?></td>
                  <td class="px-4 py-3 text-xs"><?= (int) $imp['total_errors'] ?></td>
                  <td class="px-4 py-3 text-right">
                    <a href="<?= base_url('actions/download_xml_import.php?id=' . $imp['id']) ?>" class="text-xs font-semibold text-brand-primary hover:underline">Baixar XML</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('importar_xml'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
