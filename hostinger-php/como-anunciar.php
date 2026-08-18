<?php
require_once __DIR__ . '/includes/bootstrap.php';

$features = [
    ['Anúncios com fotos e vídeos', 'Publique seus imóveis com galeria completa e atraia compradores com apresentações de qualidade.'],
    ['Cadastro manual ou por integração', 'Importe seus anúncios diretamente do seu CRM via feed VRSync ou cadastre manualmente em poucos minutos.'],
    ['Leads onde você preferir', 'Receba contatos por WhatsApp, telefone ou e-mail — sem intermediários.'],
    ['Sua página no portal', 'Tenha um espaço próprio com todos os seus imóveis, dados de contato e identidade profissional.'],
];
$pageTitle = 'Como anunciar imóveis no Habitou Imóveis';
$pageDescription = 'Anuncie no Habitou Imóveis e receba leads qualificados de quem realmente quer comprar ou alugar, em qualquer lugar do Brasil.';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-brand-navy py-16 text-center text-white">
  <div class="mx-auto max-w-3xl px-4">
    <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Para corretores e imobiliárias</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Anuncie no Habitou Imóveis e receba leads qualificados</h1>
    <p class="mt-4 text-white/70">Um portal novo, feito para todo o Brasil — conectando corretores e imobiliárias a compradores e locatários de verdade, sem taxa para quem busca.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <a href="<?= base_url('planos.php') ?>" class="inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Ver planos e anunciar</a>
      <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="inline-block rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:border-white">Cadastrar imobiliária</a>
      <a href="<?= base_url('cadastro.php?tipo=corretor') ?>" class="inline-block rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:border-white">Cadastrar como corretor</a>
    </div>
  </div>
</div>
<div class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8">
  <h2 class="mb-6 text-2xl font-bold">Tudo que você precisa para anunciar e fechar negócios</h2>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($features as [$title, $text]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5"><p class="font-semibold"><?= e($title) ?></p><p class="mt-1 text-sm text-brand-text-secondary"><?= e($text) ?></p></div>
    <?php endforeach; ?>
  </div>

  <div class="mt-14 rounded-2xl bg-brand-bg-subtle p-8 text-center">
    <h2 class="text-xl font-bold">Simples de contratar. Sem fidelidade, sem multa, sem taxa de adesão.</h2>
    <a href="<?= base_url('planos.php') ?>" class="mt-4 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Conhecer planos</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
