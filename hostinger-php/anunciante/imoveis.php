<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$user = require_login();
$properties = list_properties_for_advertiser($user);
$created = !empty($_GET['criado']);

$pageTitle = 'Meus imóveis';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <p class="mb-3 text-xs font-semibold uppercase text-brand-text-secondary">Área do anunciante</p>
      <nav class="flex flex-col gap-1">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle">Meus imóveis</a>
        <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">+ Novo imóvel</a>
        <a href="<?= base_url('minha-conta.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Minha conta</a>
      </nav>
    </aside>
    <main>
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Meus imóveis</h1>
        <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">+ Novo imóvel</a>
      </div>
      <?php if ($created): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">Imóvel criado. Adicione fotos e publique quando estiver pronto.</p><?php endif; ?>

      <?php if (empty($properties)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Você ainda não cadastrou nenhum imóvel.</div>
      <?php else: ?>
        <div class="overflow-hidden rounded-xl border border-brand-border">
          <table class="w-full text-sm">
            <thead class="bg-brand-bg-subtle text-left text-xs uppercase text-brand-text-secondary">
              <tr><th class="px-4 py-3">Imóvel</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Preço</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Ações</th></tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <?php foreach ($properties as $p): ?>
                <tr>
                  <td class="flex items-center gap-3 px-4 py-3">
                    <div class="relative h-12 w-16 shrink-0 overflow-hidden rounded bg-brand-bg-subtle">
                      <?php if (!empty($p['image_url'])): ?><img src="<?= e($p['image_url']) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
                    </div>
                    <div>
                      <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="font-medium hover:text-brand-primary"><?= e($p['title']) ?></a>
                      <p class="text-xs text-brand-text-secondary"><?= e($p['code']) ?> · <?= e($p['city_name']) ?></p>
                    </div>
                  </td>
                  <td class="px-4 py-3"><?= e(PROPERTY_TYPE_LABEL[$p['property_type']]) ?></td>
                  <td class="px-4 py-3"><?= format_currency_brl($p['price_sale'] ?? $p['price_rent']) ?></td>
                  <td class="px-4 py-3"><span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium"><?= e(PROPERTY_STATUS_LABEL[$p['status']]) ?></span></td>
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                      <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="text-brand-primary hover:underline">Editar</a>
                      <?php if ($p['status'] !== 'PUBLISHED'): ?>
                        <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline">
                          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="publish">
                          <button class="text-brand-green-hover hover:underline">Publicar</button>
                        </form>
                      <?php else: ?>
                        <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline">
                          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="pause">
                          <button class="text-brand-text-secondary hover:underline">Pausar</button>
                        </form>
                      <?php endif; ?>
                      <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="duplicate">
                        <button class="text-brand-text-secondary hover:underline">Duplicar</button>
                      </form>
                      <a href="<?= base_url('contrato-novo.php?imovel_id=' . $p['id']) ?>" class="text-brand-text-secondary hover:underline">Criar contrato</a>
                      <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline" onsubmit="return confirm('Excluir este imóvel permanentemente?');">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="delete">
                        <button class="text-red-600 hover:underline">Excluir</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
