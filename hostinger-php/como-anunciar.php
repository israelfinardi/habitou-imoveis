<?php
require_once __DIR__ . '/includes/bootstrap.php';

$features = [
    ['Anúncios com fotos e vídeos', 'Publique seus imóveis com galeria completa e atraia compradores com apresentações de qualidade.'],
    ['Cadastro manual ou por integração', 'Importe seus anúncios diretamente do seu CRM via feed VRSync ou cadastre manualmente em poucos minutos.'],
    ['Leads onde você preferir', 'Receba contatos por WhatsApp, telefone ou e-mail — sem intermediários.'],
    ['Sua página no portal', 'Tenha um espaço próprio com todos os seus imóveis, dados de contato e identidade profissional.'],
];
$testimonials = [
    ['Jorge', 'Sócio · ACRC Imóveis', 'jorge.webp', 'São mais de 20 anos de parceria — e o que me faz continuar é a combinação de um atendimento próximo e de qualidade com leads que realmente chegam preparados para negociar.'],
    ['Euclides', 'Sócio · ABVALE Imóveis', 'euclides.webp', 'Aqui você encontra credibilidade, segurança e solidez. Saber que a plataforma é exclusiva para profissionais do setor traz uma confiança que outros portais simplesmente não conseguem oferecer.'],
    ['Leonel Ribeiro', 'Sócio · Leonel Ribeiro Imóveis', 'leonel.webp', 'A visibilidade que a plataforma gera para os nossos anúncios é notável — e a qualidade dos leads que recebemos confirma isso.'],
    ['Alaor da Silva', 'Corretor de imóveis · Santa Catarina', 'alaor.webp', 'É muito mais do que um portal para anunciar imóveis — é uma ferramenta de trabalho. Para mim, o Habitou Imóveis é indispensável.'],
];

$pageTitle = 'Como anunciar imóveis em SC';
$pageDescription = 'Anuncie no Habitou Imóveis e receba leads qualificados de quem realmente quer comprar ou alugar em Santa Catarina.';
require __DIR__ . '/includes/header.php';
?>
<div class="bg-brand-navy py-16 text-center text-white">
  <div class="mx-auto max-w-3xl px-4">
    <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Para corretores e imobiliárias</p>
    <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Anuncie no Habitou Imóveis e receba leads qualificados</h1>
    <p class="mt-4 text-white/70">O portal imobiliário com maior presença em Santa Catarina. Mais de 26 anos conectando corretores e imobiliárias a compradores e locatários em todo o estado.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <a href="<?= base_url('planos.php') ?>" class="inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Ver planos e anunciar</a>
      <a href="<?= base_url('cadastro.php?tipo=imobiliaria') ?>" class="inline-block rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:border-white">Cadastrar imobiliária</a>
      <a href="<?= base_url('cadastro.php?tipo=corretor') ?>" class="inline-block rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:border-white">Cadastrar como corretor</a>
    </div>
  </div>
</div>
<div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
  <h2 class="mb-6 text-2xl font-bold">Tudo que você precisa para anunciar e fechar negócios</h2>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($features as [$title, $text]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5"><p class="font-semibold"><?= e($title) ?></p><p class="mt-1 text-sm text-brand-text-secondary"><?= e($text) ?></p></div>
    <?php endforeach; ?>
  </div>

  <h2 class="mb-6 mt-14 text-2xl font-bold">Corretores e imobiliárias que já anunciam no Habitou Imóveis</h2>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($testimonials as [$name, $role, $img, $text]): ?>
      <div class="rounded-xl border border-brand-border bg-white p-5">
        <p class="text-sm italic text-brand-text-secondary">&ldquo;<?= e($text) ?>&rdquo;</p>
        <div class="mt-4 flex items-center gap-3">
          <div class="relative h-10 w-10 overflow-hidden rounded-full"><img src="<?= base_url('assets/img/depoimentos/' . $img) ?>" class="h-full w-full object-cover" alt="<?= e($name) ?>"></div>
          <div><p class="text-sm font-semibold"><?= e($name) ?></p><p class="text-xs text-brand-text-secondary"><?= e($role) ?></p></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-14 rounded-2xl bg-brand-bg-subtle p-8 text-center">
    <h2 class="text-xl font-bold">Simples de contratar. Sem fidelidade, sem multa, sem taxa de adesão.</h2>
    <a href="<?= base_url('planos.php') ?>" class="mt-4 inline-block rounded-full bg-brand-primary px-6 py-3 text-sm font-semibold text-white hover:bg-brand-primary-hover">Conhecer planos</a>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
