<?php
require_once __DIR__ . '/includes/bootstrap.php';

$categoria = $_GET['categoria'] ?? null;
$pdo = db();
if ($categoria) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE kind = "BLOG" AND category = ? ORDER BY published_at DESC');
    $stmt->execute([$categoria]);
} else {
    $stmt = $pdo->query('SELECT * FROM articles WHERE kind = "BLOG" ORDER BY published_at DESC');
}
$posts = $stmt->fetchAll();

$catStmt = $pdo->query('SELECT category, COUNT(*) AS c FROM articles WHERE kind = "BLOG" GROUP BY category');
$categories = $catStmt->fetchAll();

$pageTitle = 'Blog';
$pageDescription = 'Análises de mercado, guias práticos e o que muda de verdade para quem compra, vende, aluga ou anuncia em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <p class="text-sm font-semibold uppercase tracking-wide text-brand-primary">Habitou Imóveis · Conteúdo</p>
  <h1 class="mt-2 text-3xl font-bold">O mercado imobiliário de Santa Catarina, explicado.</h1>
  <p class="mt-2 max-w-2xl text-brand-text-secondary">Análises de mercado, guias práticos e o que muda de verdade para quem compra, vende, aluga ou anuncia no estado.</p>

  <div class="mb-8 mt-6 flex flex-wrap gap-2">
    <a href="<?= base_url('blog.php') ?>" class="rounded-full px-3 py-1.5 text-sm <?= !$categoria ? 'bg-brand-primary text-white' : 'border border-brand-border hover:border-brand-primary' ?>">Todos</a>
    <?php foreach ($categories as $c): ?>
      <a href="<?= base_url('blog.php?categoria=' . urlencode($c['category'])) ?>" class="rounded-full px-3 py-1.5 text-sm <?= $categoria === $c['category'] ? 'bg-brand-primary text-white' : 'border border-brand-border hover:border-brand-primary' ?>"><?= e($c['category']) ?> (<?= $c['c'] ?>)</a>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($posts as $post): ?>
      <a href="<?= base_url('blog-post.php?slug=' . $post['slug']) ?>" class="rounded-xl border border-brand-border bg-white p-5 hover:border-brand-primary">
        <span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium text-brand-text-secondary"><?= e($post['category']) ?></span>
        <p class="mt-3 font-semibold"><?= e($post['title']) ?></p>
        <p class="mt-1 line-clamp-2 text-sm text-brand-text-secondary"><?= e($post['excerpt']) ?></p>
        <p class="mt-3 text-xs text-brand-text-secondary"><?= e($post['author_name']) ?> · <?= format_date($post['published_at']) ?><?= $post['read_minutes'] ? ' · ' . $post['read_minutes'] . ' min de leitura' : '' ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
