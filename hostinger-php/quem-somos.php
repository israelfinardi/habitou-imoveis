<?php
require_once __DIR__ . '/includes/bootstrap.php';

$values = [
    ['Parceria real', 'Estamos ao lado dos corretores — não competimos com eles. Nossa missão é entregar audiência, ferramentas e presença digital aos profissionais.'],
    ['Exclusividade profissional', 'Somente corretores e imobiliárias credenciados pelo CRECI-SC anunciam aqui. Isso garante qualidade, ética e confiança em cada anúncio.'],
    ['Raízes catarinenses', 'Somos de SC, para SC. Focamos no mercado local desde o primeiro dia, cidade por cidade.'],
    ['Simplicidade', 'Cadastro rápido, sem burocracia e sem fidelidade — o corretor entra e sai quando quiser.'],
];

$pageTitle = 'Quem somos';
$pageDescription = 'Conheça o Habitou Imóveis: um portal novo, feito para conectar corretores e imobiliárias de Santa Catarina a compradores e locatários.';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-brand-navy py-16 text-center text-white">
  <div class="mx-auto max-w-3xl px-4">
    <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Quem somos</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Um portal novo, feito para o corretor catarinense.</h1>
    <p class="mt-4 text-white/70">O Habitou Imóveis acabou de nascer. Somos uma plataforma nova, construída do zero para conectar quem procura um imóvel em Santa Catarina diretamente com corretores e imobiliárias verificados pelo CRECI — sem intermediários e sem taxa para quem busca.</p>
  </div>
</div>
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <h2 class="mb-2 text-2xl font-bold">Como funciona</h2>
  <p class="mb-6 text-sm text-brand-text-secondary">Nosso modelo é simples: de um lado, compradores e locatários buscam imóveis gratuitamente. Do outro, corretores e imobiliárias publicam seus anúncios e recebem contato direto de quem tem interesse real.</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-brand-border bg-white p-5">
      <p class="font-semibold">1. Busca gratuita</p>
      <p class="mt-1 text-sm text-brand-text-secondary">Quem procura um imóvel usa o portal sem pagar nada e fala diretamente com o anunciante.</p>
    </div>
    <div class="rounded-xl border border-brand-border bg-white p-5">
      <p class="font-semibold">2. Anúncio verificado</p>
      <p class="mt-1 text-sm text-brand-text-secondary">Só corretores e imobiliárias com CRECI ativo podem publicar imóveis na plataforma.</p>
    </div>
    <div class="rounded-xl border border-brand-border bg-white p-5">
      <p class="font-semibold">3. Planos de assinatura</p>
      <p class="mt-1 text-sm text-brand-text-secondary">O portal se sustenta pelos planos pagos por corretores e imobiliárias — não cobramos nada de quem busca um imóvel.</p>
    </div>
  </div>

  <h2 class="mb-2 mt-14 text-2xl font-bold">Segurança e legalidade</h2>
  <p class="mb-6 text-sm text-brand-text-secondary">Toda locação e compra e venda de imóvel no Brasil é regulada pela Lei do Inquilinato (Lei nº 8.245/1991) e pelo Código Civil. No Habitou Imóveis, cada anúncio parte de um corretor ou imobiliária com CRECI verificado — a negociação, a documentação e o contrato seguem sempre os trâmites legais, combinados diretamente entre você e o profissional responsável pelo imóvel.</p>

  <h2 class="mb-2 mt-14 text-2xl font-bold">O corretor é o protagonista. Sempre foi.</h2>
  <p class="mb-6 text-sm text-brand-text-secondary">O mercado imobiliário de Santa Catarina é construído por corretores e imobiliárias. O Habitou Imóveis existe para fortalecer esses profissionais — nunca para concorrer com eles.</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($values as [$title, $text]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5"><p class="font-semibold"><?= e($title) ?></p><p class="mt-1 text-sm text-brand-text-secondary"><?= e($text) ?></p></div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
