<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
require_once __DIR__ . '/../includes/account_nav.php';

$user = require_login();
$properties = list_properties_for_advertiser($user);
$created = !empty($_GET['criado']);

$pageTitle = 'Meus imóveis';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Meus imóveis</h1>
        <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">+ Novo imóvel</a>
      </div>
      <?php if ($created): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">Imóvel criado. Adicione fotos e publique quando estiver pronto.</p><?php endif; ?>

      <?php if (empty($properties)): ?>
        <div class="rounded-xl border border-dashed border-brand-border p-12 text-center text-brand-text-secondary">Você ainda não cadastrou nenhum imóvel.</div>
      <?php else: ?>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($properties as $p): ?>
            <div class="overflow-hidden rounded-2xl border border-brand-border">
              <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="relative block aspect-[4/3] w-full bg-brand-bg-subtle">
                <?php if (!empty($p['image_url'])): ?><img src="<?= e($p['image_url']) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
                <span class="absolute left-2 top-2 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-brand-text shadow"><?= e(PROPERTY_STATUS_LABEL[$p['status']]) ?></span>
              </a>
              <div class="p-3.5">
                <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="block truncate text-[15px] font-semibold text-brand-text hover:text-brand-primary"><?= e($p['title']) ?></a>
                <p class="mt-0.5 truncate text-xs text-brand-text-secondary"><?= e($p['code']) ?> · <?= e($p['city_name']) ?></p>
                <p class="mt-1 text-sm text-brand-text-secondary"><?= e(PROPERTY_TYPE_LABEL[$p['property_type']]) ?></p>
                <p class="mt-1 text-[15px] font-semibold text-brand-text"><?= format_currency_brl($p['price_sale'] ?? $p['price_rent']) ?></p>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 border-t border-brand-border pt-3 text-xs">
                  <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="font-semibold text-brand-primary hover:underline">Editar</a>
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
                  <a href="<?= base_url('contrato-novo.php?imovel_id=' . $p['id']) ?>" class="text-brand-text-secondary hover:underline">Contrato</a>
                  <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline" onsubmit="return confirm('Excluir este imóvel permanentemente?');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="delete">
                    <button class="text-red-600 hover:underline">Excluir</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
    <aside><?php render_account_nav('anuncios', in_array($user['role'], ['AGENCY_ADMIN'], true)); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
