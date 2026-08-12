<?php
require_once __DIR__ . '/includes/bootstrap.php';

$slug = $_GET['slug'] ?? '';
$stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND kind = "GUIDE"');
$stmt->execute([$slug]);
$article = $stmt->fetch();
if (!$article) {
    http_response_code(404);
    $pageTitle = 'Artigo não encontrado';
    require __DIR__ . '/includes/header.php';
    echo '<div class="mx-auto max-w-3xl px-4 py-20 text-center"><h1 class="text-2xl font-bold">Artigo não encontrado</h1></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $article['title'];
$pageDescription = $article['excerpt'];
require __DIR__ . '/includes/header.php';
?>
<article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
  <a href="<?= base_url('guias.php') ?>" class="mb-4 inline-block text-sm text-brand-primary hover:underline">← Voltar para a central de ajuda</a>
  <span class="rounded-full bg-brand-bg-subtle px-2 py-1 text-xs font-medium text-brand-text-secondary"><?= e($article['category']) ?></span>
  <h1 class="mt-3 text-3xl font-bold"><?= e($article['title']) ?></h1>
  <p class="mt-8 text-lg leading-relaxed"><?= e($article['excerpt']) ?></p>
  <?php if ($article['content']): ?><div class="prose mt-6 whitespace-pre-line text-brand-text-secondary"><?= nl2br(e($article['content'])) ?></div><?php endif; ?>
  <p class="mt-10 text-sm text-brand-text-secondary">Não resolveu? <a href="<?= base_url('fale-conosco.php') ?>" class="text-brand-primary hover:underline">Fale com a gente</a>.</p>
</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
