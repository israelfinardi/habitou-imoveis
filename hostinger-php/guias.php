<?php
require_once __DIR__ . '/includes/bootstrap.php';

$articles = db()->query('SELECT * FROM articles WHERE kind = "GUIDE" ORDER BY published_at DESC')->fetchAll();
$categories = db()->query('SELECT category, COUNT(*) AS c FROM articles WHERE kind = "GUIDE" GROUP BY category')->fetchAll();

$pageTitle = 'Central de ajuda';
$pageDescription = 'Guias e respostas para dúvidas frequentes sobre o Habitou Imóveis.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
  <h1 class="mb-2 text-3xl font-bold">Como podemos te ajudar?</h1>
  <p class="mb-8 text-brand-text-secondary">Não encontrou o que procura? <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">Fale com a gente</a>.</p>
  <div class="mb-8 flex flex-wrap gap-2">
    <?php foreach ($categories as $c): ?><span class="rounded-full border border-brand-border px-3 py-1.5 text-sm"><?= e($c['category']) ?> (<?= $c['c'] ?>)</span><?php endforeach; ?>
  </div>
  <h2 class="mb-4 text-lg font-bold">Últimos artigos</h2>
  <div class="space-y-3">
    <?php foreach ($articles as $a): ?>
      <a href="<?= base_url('guia.php?slug=' . $a['slug']) ?>" class="block rounded-xl border border-brand-border bg-white p-4 hover:border-brand-primary">
        <span class="text-xs font-medium text-brand-text-secondary"><?= e($a['category']) ?></span>
        <p class="font-semibold"><?= e($a['title']) ?></p>
        <p class="mt-1 text-sm text-brand-text-secondary"><?= e($a['excerpt']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
