<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/property_form.php';
require_once __DIR__ . '/../includes/property_mutations.php';

$user = require_login();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $input = parse_property_form($_POST);
    if (mb_strlen($input['title']) < 10) {
        $error = 'O título deve ter pelo menos 10 caracteres.';
    } elseif (!$input['cidade'] || !$input['bairro']) {
        $error = 'Selecione a cidade e informe o bairro.';
    } else {
        try {
            $id = create_property($input, $user);
            redirect(base_url('anunciante/editar.php?id=' . $id . '&criado=1'));
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Novo imóvel';
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-8 lg:grid-cols-[220px_1fr]">
    <aside>
      <nav class="flex flex-col gap-1">
        <a href="<?= base_url('anunciante/imoveis.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-brand-bg-subtle">Meus imóveis</a>
        <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-lg px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle">+ Novo imóvel</a>
      </nav>
    </aside>
    <main>
      <h1 class="mb-6 text-2xl font-bold">Novo imóvel</h1>
      <?php if ($error): ?><p class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"><?= e($error) ?></p><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <?php render_property_form($_POST); ?>
        <button type="submit" class="rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Criar imóvel</button>
      </form>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
