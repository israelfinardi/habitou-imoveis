<?php
require_once __DIR__ . '/includes/bootstrap.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$pageSize = 12;
$offset = ($page - 1) * $pageSize;

$where = 'status = "ACTIVE"';
$args = [];
if ($q) {
    $where .= ' AND name LIKE ?';
    $args[] = '%' . $q . '%';
}

$pdo = db();
$total = (int) (function () use ($pdo, $where, $args) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agencies WHERE $where");
    $stmt->execute($args);
    return $stmt->fetchColumn();
})();

$stmt = $pdo->prepare("SELECT a.*, (SELECT COUNT(*) FROM properties p WHERE p.agency_id = a.id AND p.status = 'PUBLISHED') AS property_count
    FROM agencies a WHERE $where ORDER BY name LIMIT $offset, $pageSize");
$stmt->execute($args);
$agencies = $stmt->fetchAll();
$totalPages = max(1, (int) ceil($total / $pageSize));

$pageTitle = 'Imobiliárias e corretores';
$pageDescription = 'Conheça as imobiliárias parceiras Habitou Imóveis em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-[1800px] px-4 py-8 sm:px-6 lg:px-8">
  <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <h1 class="mb-1 text-2xl font-bold">Imobiliárias e corretores</h1>
      <p class="text-sm text-brand-text-secondary"><?= $total ?> imobiliárias parceiras</p>
    </div>
    <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="shrink-0 rounded-full bg-brand-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-primary-hover">Cadastrar minha imobiliária</a>
  </div>

  <form class="mb-6 max-w-sm">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar imobiliária..." class="w-full rounded-lg border border-brand-border px-3 py-2 text-sm">
  </form>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($agencies as $a): ?>
      <a href="<?= base_url('imobiliaria.php?slug=' . $a['slug']) ?>" class="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
        <div class="mb-3 flex items-center gap-3">
          <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-full bg-brand-bg-subtle">
            <?php if ($a['logo_url']): ?><img src="<?= e($a['logo_url']) ?>" class="h-full w-full object-cover" alt=""><?php endif; ?>
          </div>
          <div>
            <p class="font-semibold"><?= e($a['name']) ?></p>
            <p class="text-xs text-brand-text-secondary"><?= $a['property_count'] ?> imóveis publicados</p>
          </div>
        </div>
        <?php if ($a['description']): ?><p class="line-clamp-2 text-xs text-brand-text-secondary"><?= e($a['description']) ?></p><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="mt-8 flex items-center justify-center gap-1">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a href="<?= base_url('imobiliarias.php?' . http_build_query(['q' => $q, 'pagina' => $p])) ?>" class="rounded-md border px-3 py-1.5 text-sm <?= $p === $page ? 'border-brand-primary bg-brand-primary text-white' : 'border-brand-border hover:border-brand-primary' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
