<?php
require_once __DIR__ . '/includes/bootstrap.php';

$timeline = [
    ['1999', 'Fundação', 'O Habitou Imóveis nasce — pioneiro digital do setor. Criado com uma visão à frente do seu tempo: levar o mercado imobiliário de Santa Catarina para a internet, em um momento em que poucos acreditavam no potencial do ambiente digital.'],
    ['2000s', 'Expansão pelo Vale do Itajaí', 'Consolidamos presença no Vale do Itajaí — uma das regiões de maior dinamismo imobiliário do estado. A marca se tornou referência entre corretores e imobiliárias de Blumenau, Itajaí e cidades vizinhas.'],
    ['2010s', 'Audiência consolidada', 'O Habitou Imóveis chegou à marca de 2 milhões de usuários únicos por ano — uma audiência qualificada e engajada, com tempo médio de permanência superior a 10 minutos no portal.'],
    ['2025', 'Decisão de reinvenção tecnológica', 'Com 25 anos de história e uma base sólida, iniciamos o maior investimento tecnológico da nossa trajetória: o desenvolvimento completo de uma nova plataforma.'],
    ['2026', 'Nova era', 'Lançamos a nova versão do Habitou Imóveis: uma plataforma completa para corretores e imobiliárias, construída sobre 26 anos de confiança em Santa Catarina.'],
];
$stats = [['1999', 'Um dos primeiros portais imobiliários criados no Brasil.'], ['26 anos', 'De presença contínua no mercado de Santa Catarina.'], ['2M+/ano', 'Usuários únicos qualificados navegando no portal todos os anos.']];
$values = [
    ['Parceria real', 'Estamos ao lado dos corretores — não competimos com eles. Nossa missão é entregar audiência, ferramentas e presença digital aos profissionais.'],
    ['Exclusividade profissional', 'Somente corretores e imobiliárias credenciados pelo CRECI-SC anunciam aqui. Isso garante qualidade, ética e confiança em cada anúncio.'],
    ['Raízes catarinenses', 'Somos de SC, para SC. Conhecemos o mercado local — e isso se reflete na qualidade da audiência e na relevância dos anúncios.'],
    ['Confiança construída', '26 anos de presença contínua constroem algo que não se compra: reputação.'],
];

$pageTitle = 'Quem somos';
$pageDescription = '26 anos de história conectando corretores, imobiliárias e famílias em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-brand-navy py-16 text-center text-white">
  <div class="mx-auto max-w-3xl px-4">
    <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Quem somos</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-4xl">26 anos de história e uma nova tecnologia para liderar o futuro.</h1>
    <p class="mt-4 text-white/70">O Habitou Imóveis nasceu junto com o mercado imobiliário digital no Brasil. Agora, com a mais nova versão da plataforma, reafirmamos nosso compromisso de estar sempre à frente — ao lado dos corretores e imobiliárias que constroem Santa Catarina.</p>
  </div>
</div>
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <?php foreach ($stats as [$v, $l]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5 text-center"><p class="text-2xl font-bold text-brand-primary"><?= e($v) ?></p><p class="mt-1 text-sm text-brand-text-secondary"><?= e($l) ?></p></div>
    <?php endforeach; ?>
  </div>

  <h2 class="mb-6 mt-14 text-2xl font-bold">Nossa história</h2>
  <div class="space-y-6 border-l-2 border-brand-border pl-6">
    <?php foreach ($timeline as [$year, $title, $text]): ?>
      <div class="relative">
        <span class="absolute -left-[31px] flex h-4 w-4 items-center justify-center rounded-full bg-brand-primary"></span>
        <p class="text-sm font-semibold text-brand-primary"><?= e($year) ?></p>
        <p class="font-semibold"><?= e($title) ?></p>
        <p class="mt-1 text-sm text-brand-text-secondary"><?= e($text) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <h2 class="mb-2 mt-14 text-2xl font-bold">O corretor é o protagonista. Sempre foi.</h2>
  <p class="mb-6 text-sm text-brand-text-secondary">O mercado imobiliário de Santa Catarina é construído por corretores e imobiliárias. O Habitou Imóveis existe para fortalecer esses profissionais — nunca para concorrer com eles.</p>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($values as [$title, $text]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5"><p class="font-semibold"><?= e($title) ?></p><p class="mt-1 text-sm text-brand-text-secondary"><?= e($text) ?></p></div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
