<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_mutations.php';
require_once __DIR__ . '/../includes/account_nav.php';

$user = require_login();
$properties = list_properties_for_advertiser($user);
$created = !empty($_GET['criado']);
$limitReached = !empty($_GET['limite']);

$plan = get_effective_plan_for_actor($user);
$listingCount = count_active_listings_for_actor($user);
$atCap = $user['role'] !== 'ADMIN' && $plan['max_listings'] !== null && $listingCount >= $plan['max_listings'];

if (($_GET['export'] ?? '') === 'csv') {
    export_csv('meus-imoveis.csv', [
        'code' => 'Código', 'title' => 'Título', 'status' => 'Status', 'property_type' => 'Tipo',
        'city_name' => 'Cidade', 'price_sale' => 'Preço venda', 'price_rent' => 'Preço aluguel', 'published_at' => 'Publicado em',
    ], array_map(fn($p) => [
        'code' => $p['code'], 'title' => $p['title'], 'status' => PROPERTY_STATUS_LABEL[$p['status']] ?? $p['status'],
        'property_type' => PROPERTY_TYPE_LABEL[$p['property_type']] ?? $p['property_type'], 'city_name' => $p['city_name'],
        'price_sale' => $p['price_sale'], 'price_rent' => $p['price_rent'], 'published_at' => $p['published_at'],
    ], $properties));
}

$pageTitle = 'Meus imóveis';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_220px]">
    <main>
      <div class="mb-3 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Meus imóveis</h1>
        <div class="flex items-center gap-2">
          <?php render_csv_export_button(); ?>
          <a href="<?= base_url('actions/export_properties_xml.php') ?>" class="inline-flex items-center gap-1.5 rounded-full border border-brand-border px-4 py-2 text-sm font-semibold text-brand-text hover:border-brand-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 21h16"/></svg>
            Exportar XML
          </a>
          <?php if ($atCap): ?>
            <a href="<?= base_url('planos.php') ?>" class="rounded-full border border-brand-primary px-4 py-2 text-sm font-semibold text-brand-primary hover:bg-brand-primary/5">Assinar plano</a>
          <?php else: ?>
            <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">+ Novo imóvel</a>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($user['role'] !== 'ADMIN'): ?>
        <p class="mb-6 text-sm text-brand-text-secondary">
          Plano <strong class="text-brand-text"><?= e($plan['plan_name']) ?></strong> ·
          <?= $listingCount ?><?= $plan['max_listings'] !== null ? '/' . $plan['max_listings'] : '' ?> imóveis usados ·
          <?= $plan['eternal'] ? 'anúncios eternos, sem expiração' : 'cada anúncio publicado vale por ' . FREE_PLAN_VALIDITY_DAYS . ' dias, depois precisa ser renovado' ?>
        </p>
      <?php endif; ?>
      <?php if ($created): ?><p class="mb-4 rounded-lg bg-brand-green/10 px-3 py-2 text-sm text-brand-green-hover">Imóvel criado. Adicione fotos e publique quando estiver pronto.</p><?php endif; ?>
      <?php if ($limitReached): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">Você atingiu o limite de imóveis do seu plano. Arquive um anúncio ou assine um plano com mais vagas para cadastrar outro.</p><?php endif; ?>

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
                <p class="mt-0.5 truncate text-xs text-brand-text-secondary">
                  <?= e($p['code']) ?> · <?= e($p['city_name']) ?>
                  <?php if (($p['origin'] ?? 'MANUAL') === 'XML_IMPORT'): ?><span class="ml-1 rounded-full bg-brand-primary/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-brand-primary">XML</span><?php endif; ?>
                </p>
                <p class="mt-1 text-sm text-brand-text-secondary"><?= e(PROPERTY_TYPE_LABEL[$p['property_type']]) ?></p>
                <p class="mt-1 text-[15px] font-semibold text-brand-text"><?= format_currency_brl($p['price_sale'] ?? $p['price_rent']) ?></p>
                <?php if ($p['status'] === 'PUBLISHED' && !empty($p['expires_at'])): ?>
                  <p class="mt-1 text-xs text-brand-text-secondary">Expira em <?= e(format_date($p['expires_at'])) ?></p>
                <?php elseif ($p['status'] === 'EXPIRED'): ?>
                  <p class="mt-1 text-xs font-semibold text-red-600">Expirado — renove para voltar a aparecer no site</p>
                <?php endif; ?>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 border-t border-brand-border pt-3 text-xs">
                  <a href="<?= base_url('anunciante/editar.php?id=' . $p['id']) ?>" class="font-semibold text-brand-primary hover:underline">Editar</a>
                  <?php if ($p['status'] === 'PUBLISHED'): ?>
                    <a href="<?= e(property_href($p)) ?>" target="_blank" rel="noopener noreferrer" class="text-brand-text-secondary hover:underline">Ver anúncio</a>
                  <?php endif; ?>
                  <?php if ($p['status'] === 'EXPIRED'): ?>
                    <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="renew">
                      <button class="font-semibold text-brand-green-hover hover:underline">Renovar</button>
                    </form>
                  <?php elseif ($p['status'] !== 'PUBLISHED'): ?>
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
                  <?php if (!$atCap): ?>
                    <form method="post" action="<?= base_url('actions/property_action.php') ?>" class="inline">
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="do" value="duplicate">
                      <button class="text-brand-text-secondary hover:underline">Duplicar</button>
                    </form>
                  <?php endif; ?>
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
    <aside><?php render_account_nav('anuncios'); ?></aside>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
