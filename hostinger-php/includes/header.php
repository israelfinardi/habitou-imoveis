<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/constants.php';
$__user = current_user();
$__pageTitle = $pageTitle ?? APP_NAME;
$__pageDescription = $pageDescription ?? 'Encontre apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina.';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($__pageTitle) ?><?= $__pageTitle !== APP_NAME ? ' | ' . APP_NAME : '' ?></title>
<meta name="description" content="<?= e($__pageDescription) ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: {
            primary: '#c1502e',
            'primary-hover': '#d45f3a',
            dark: '#7a2e12',
            light: '#de6b46',
            text: '#2b2b2b',
            'text-secondary': '#585b62',
            border: '#e5e5ea',
            'bg-subtle': '#f9f9fb',
            green: '#00bc7d',
            'green-hover': '#00a36c',
            navy: '#3d1d10',
          },
        },
      },
    },
  };
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Public Sans',Arial,sans-serif}
.price-pin{background:transparent;border:0}
.price-pin-label{
  display:inline-block;white-space:nowrap;transform:translate(-50%,-100%);
  background:#fff;color:#2b2b2b;border:1px solid #e5e5ea;border-radius:9999px;
  padding:5px 11px;font-size:12px;font-weight:700;box-shadow:0 1px 5px rgba(0,0,0,.18);
  cursor:pointer;
}
.price-pin-label.active,.price-pin-label:hover{background:#c1502e;color:#fff;border-color:#c1502e;z-index:1000!important}
.map-pin-popup .leaflet-popup-content-wrapper{padding:0;border-radius:12px;overflow:hidden}
.map-pin-popup .leaflet-popup-content{margin:0;width:100%!important}
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="flex min-h-screen flex-col bg-white text-brand-text">

<header class="sticky top-0 z-40 border-b border-brand-border bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
    <a href="<?= base_url('/') ?>" class="flex shrink-0 items-center gap-2">
      <span class="text-xl font-bold tracking-tight text-brand-primary">Habitou</span>
      <span class="hidden text-xl font-light sm:inline">Imóveis</span>
    </a>

    <nav class="hidden items-center gap-6 lg:flex">
      <div class="group relative">
        <button class="flex items-center gap-1 py-2 text-sm font-medium hover:text-brand-primary">Comprar</button>
        <div class="invisible absolute left-0 top-full z-30 w-56 rounded-lg border border-brand-border bg-white p-2 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100">
          <?php foreach (FEATURED_CITIES as $c): ?>
            <a href="<?= base_url('cidade.php?slug=' . $c['slug'] . '&transacao=comprar') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle hover:text-brand-primary"><?= e($c['name']) ?></a>
          <?php endforeach; ?>
          <a href="<?= base_url('imoveis.php') ?>" class="mt-1 block rounded-md border-t border-brand-border px-3 py-2 text-sm font-medium text-brand-primary hover:bg-brand-bg-subtle">Ver todos os imóveis</a>
        </div>
      </div>
      <div class="group relative">
        <button class="flex items-center gap-1 py-2 text-sm font-medium hover:text-brand-primary">Alugar</button>
        <div class="invisible absolute left-0 top-full z-30 w-56 rounded-lg border border-brand-border bg-white p-2 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100">
          <?php foreach (FEATURED_CITIES as $c): ?>
            <a href="<?= base_url('cidade.php?slug=' . $c['slug'] . '&transacao=alugar') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle hover:text-brand-primary"><?= e($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="<?= base_url('imobiliarias.php') ?>" class="text-sm font-medium hover:text-brand-primary">Imobiliárias e corretores</a>
      <a href="<?= base_url('como-anunciar.php') ?>" class="text-sm font-medium hover:text-brand-primary">Como anunciar</a>
      <a href="<?= base_url('quem-somos.php') ?>" class="text-sm font-medium hover:text-brand-primary">Sobre nós</a>
    </nav>

    <div class="hidden items-center gap-3 lg:flex">
      <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Anunciar imóvel</a>
      <?php if ($__user): ?>
        <div class="group relative">
          <button class="flex items-center gap-2 rounded-full border border-brand-border px-3 py-1.5 text-sm font-medium hover:border-brand-primary">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white"><?= e(mb_strtoupper(mb_substr($__user['first_name'], 0, 1))) ?></span>
            <?= e($__user['first_name']) ?>
          </button>
          <div class="invisible absolute right-0 top-full z-30 mt-2 w-56 rounded-lg border border-brand-border bg-white p-2 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100">
            <a href="<?= base_url('minha-conta.php') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">Minha conta</a>
            <a href="<?= base_url('anunciante/imoveis.php') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">Meus anúncios</a>
            <a href="<?= base_url('minha-conta-favoritos.php') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">Favoritos</a>
            <?php if (in_array($__user['role'], ['AGENCY_ADMIN', 'AGENT'], true)): ?>
              <a href="<?= base_url('imobiliaria/feeds.php') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">Painel da imobiliária</a>
            <?php endif; ?>
            <?php if ($__user['role'] === 'ADMIN'): ?>
              <a href="<?= base_url('admin/index.php') ?>" class="block rounded-md px-3 py-2 text-sm hover:bg-brand-bg-subtle">Administração</a>
            <?php endif; ?>
            <a href="<?= base_url('logout.php') ?>" class="mt-1 block rounded-md border-t border-brand-border px-3 py-2 text-sm text-brand-text-secondary hover:bg-brand-bg-subtle">Sair</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= base_url('login.php') ?>" class="rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary hover:text-brand-primary">Entrar</a>
        <a href="<?= base_url('cadastro.php') ?>" class="text-sm font-medium hover:text-brand-primary">Cadastre-se</a>
      <?php endif; ?>
    </div>

    <button id="mobile-menu-btn" class="flex h-9 w-9 items-center justify-center rounded-md border border-brand-border lg:hidden" aria-label="Abrir menu">
      <svg width="18" height="14" viewBox="0 0 18 14" fill="none"><path d="M0 1h18M0 7h18M0 13h18" stroke="currentColor" stroke-width="1.5"/></svg>
    </button>
  </div>

  <div id="mobile-menu" class="hidden lg:hidden border-t border-brand-border bg-white p-4">
    <?php if ($__user): ?>
      <div class="mb-3 rounded-lg bg-brand-bg-subtle p-3 text-sm">
        <p class="font-semibold"><?= e($__user['first_name'] . ' ' . $__user['last_name']) ?></p>
        <p class="text-brand-text-secondary"><?= e($__user['email']) ?></p>
      </div>
    <?php else: ?>
      <div class="mb-3 flex gap-2">
        <a href="<?= base_url('login.php') ?>" class="flex-1 rounded-full border border-brand-border py-2 text-center text-sm font-medium">Entrar</a>
        <a href="<?= base_url('cadastro.php') ?>" class="flex-1 rounded-full bg-brand-primary py-2 text-center text-sm font-medium text-white">Cadastre-se</a>
      </div>
    <?php endif; ?>
    <p class="mb-1 mt-2 text-xs font-semibold uppercase text-brand-text-secondary">Cidades</p>
    <?php foreach (FEATURED_CITIES as $c): ?>
      <a href="<?= base_url('cidade.php?slug=' . $c['slug']) ?>" class="block py-1.5 text-sm"><?= e($c['name']) ?></a>
    <?php endforeach; ?>
    <div class="mt-3 flex flex-col gap-1 border-t border-brand-border pt-3">
      <a href="<?= base_url('imobiliarias.php') ?>" class="py-1.5 text-sm">Imobiliárias e corretores</a>
      <a href="<?= base_url('como-anunciar.php') ?>" class="py-1.5 text-sm">Como anunciar</a>
      <a href="<?= base_url('quem-somos.php') ?>" class="py-1.5 text-sm">Sobre nós</a>
      <a href="<?= base_url('anunciante/novo.php') ?>" class="mt-2 rounded-full bg-brand-primary px-4 py-2 text-center text-sm font-semibold text-white">Anunciar imóvel</a>
      <?php if ($__user): ?><a href="<?= base_url('logout.php') ?>" class="py-1.5 text-sm text-brand-text-secondary">Sair</a><?php endif; ?>
    </div>
  </div>
</header>
<script>
document.getElementById('mobile-menu-btn')?.addEventListener('click', function () {
  document.getElementById('mobile-menu')?.classList.toggle('hidden');
});
</script>
<main class="flex-1">
