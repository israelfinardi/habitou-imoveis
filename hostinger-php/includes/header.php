<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/constants.php';
$__user = current_user();
$__pageTitle = $pageTitle ?? APP_NAME;
$__pageDescription = $pageDescription ?? 'Encontre apartamentos, casas e terrenos para comprar ou alugar em Santa Catarina.';

// Estado atual dos filtros (transação/cidade), usado para manter a barra de
// busca superior sincronizada com o que já está aplicado na página.
$__topbarTransacao = $_GET['transacao'] ?? '';
if (!empty($city['name']) && !empty($city['state_code'])) {
    $__topbarCidade = $city['name'] . ' (' . $city['state_code'] . ')';
} else {
    $__topbarCidade = $_GET['cidade_nome'] ?? '';
}
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
            primary: '#C1502E',
            'primary-hover': '#D45F3A',
            dark: '#7A2E12',
            light: '#DE6B46',
            text: '#222222',
            'text-secondary': '#717171',
            border: '#DDDDDD',
            'bg-subtle': '#F8F6F4',
            green: '#25D366',
            'green-hover': '#1DA851',
            navy: '#3D1D10',
          },
        },
        fontFamily: {
          sans: ['Inter', 'system-ui', 'sans-serif'],
        },
      },
    },
  };
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',Arial,sans-serif}
.price-pin{background:transparent;border:0}
.price-pin-label{
  display:inline-block;white-space:nowrap;transform:translate(-50%,-100%);
  background:#fff;color:#222222;border:1px solid #DDDDDD;border-radius:9999px;
  padding:5px 11px;font-size:12px;font-weight:700;box-shadow:0 1px 5px rgba(0,0,0,.18);
  cursor:pointer;
}
.price-pin-label.active,.price-pin-label:hover{background:#C1502E;color:#fff;border-color:#C1502E;z-index:1000!important}
.map-pin-popup .leaflet-popup-content-wrapper{padding:0;border-radius:12px;overflow:hidden}
.map-pin-popup .leaflet-popup-content{margin:0;width:100%!important}

/* --- Barra de busca única estilo Airbnb (cidade | transação | botão) --- */
.airbnb-bar{display:flex;align-items:center;background:#fff;border:1px solid #DDDDDD;border-radius:999px;box-shadow:0 1px 2px rgba(0,0,0,.08);transition:box-shadow .2s;padding:6px}
.airbnb-bar:hover,.airbnb-bar:focus-within{box-shadow:0 3px 12px rgba(0,0,0,.16)}
.cidade-pill{position:relative;flex:none}
.cidade-pill-btn{display:flex;align-items:center;gap:8px;background:0 0;border:0;border-radius:999px;padding:10px 20px;font-weight:600;font-size:14px;color:#222222;cursor:pointer}
.cidade-pill-btn:hover{background:#F7F7F7}
.cidade-pill-btn svg{width:16px;height:16px;color:#717171;flex:none}
.cidade-pill.on .cidade-pill-btn{background:#EBEBEB}
.cidade-pill.on .cidade-dropdown{display:block}
.cidade-dropdown{display:none;position:absolute;top:calc(100% + 10px);left:0;width:320px;max-width:88vw;background:#fff;border-radius:18px;box-shadow:0 30px 70px -30px rgba(0,0,0,.5);border:1px solid #EBEBEB;padding:14px;z-index:220}
.cidade-dropdown input{width:100%;border:1px solid #DDDDDD;border-radius:10px;padding:11px 14px;font-size:14.5px;box-sizing:border-box}
.cidade-dropdown input:focus{outline:2px solid #222222;outline-offset:1px}
.cidade-sugestoes{margin-top:8px;max-height:320px;overflow-y:auto}
.cidade-pill--field{width:100%}
.cidade-pill--field .cidade-pill-btn{width:100%;justify-content:flex-start;border:1px solid #DDDDDD;border-radius:12px;padding:10px 12px;font-weight:400;color:#222}
.cidade-pill--field .cidade-pill-btn:hover{background:#fff;border-color:#222}
.cidade-pill--field.on .cidade-pill-btn{background:#fff}
.cidade-pill--field .cidade-pill-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cidade-pill--field .cidade-dropdown{width:100%}
.cidade-sugestao{display:flex;align-items:center;gap:12px;padding:10px 8px;border-radius:10px;cursor:pointer;font-size:14.5px}
.cidade-sugestao:hover{background:#F7F7F7}
.cidade-sugestao .ic{width:34px;height:34px;border-radius:50%;background:#F7F7F7;display:flex;align-items:center;justify-content:center;flex:none}
.cidade-sugestao .ic svg{width:16px;height:16px;color:#717171}
.cidade-sugestao b{font-weight:700}
.cidade-sugestao-vazio{color:#717171;font-size:13.5px;padding:10px 8px}
.airbnb-bar-divider{width:1px;height:24px;background:#DDDDDD;flex:none}
.sp-seg{border:0;background:0 0;padding:10px 16px;border-radius:999px;cursor:pointer;font-weight:600;font-size:14px;color:#222222;transition:.16s;white-space:nowrap;text-decoration:none;display:inline-block}
.sp-seg:hover{background:#F7F7F7}
.sp-seg.on{background:#EBEBEB;font-weight:700}
.airbnb-bar-btn{display:flex;align-items:center;justify-content:center;width:40px;height:40px;margin-left:4px;border-radius:999px;background:#C1502E;color:#fff;border:0;cursor:pointer;flex:none;transition:background .16s}
.airbnb-bar-btn:hover{background:#D45F3A}
.airbnb-bar-btn svg{width:16px;height:16px}
.filtros-pill-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #DDDDDD;border-radius:999px;padding:10px 16px;font-size:13.5px;font-weight:700;cursor:pointer;white-space:nowrap;background:#fff;transition:.16s;text-decoration:none;color:#222222;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.filtros-pill-btn:hover{box-shadow:0 2px 10px rgba(0,0,0,.14)}
.filtros-pill-btn svg{width:16px;height:16px;flex:none}
.nav-user-btn{display:flex;align-items:center;gap:10px;border:1px solid #DDDDDD;border-radius:999px;padding:6px 6px 6px 13px;background:#fff;cursor:pointer;transition:box-shadow .16s,border-color .16s}
.nav-user-btn:hover{box-shadow:0 2px 10px rgba(0,0,0,.16);border-color:#EBEBEB}
.nav-user-menu{position:absolute;top:calc(100% + 10px);right:0;background:#fff;border:1px solid #EBEBEB;border-radius:14px;box-shadow:0 10px 32px rgba(0,0,0,.18);min-width:220px;padding:8px;display:none;z-index:200}
.nav-user-menu.on{display:block}
.nav-user-menu a{display:block;padding:9px 12px;border-radius:8px;font-size:14px;font-weight:600;color:#222222;text-decoration:none}
.nav-user-menu a:hover{background:#F7F7F7}
.scrollbar-none{scrollbar-width:none}
.scrollbar-none::-webkit-scrollbar{display:none}
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="flex min-h-screen flex-col bg-white text-brand-text">

<header class="sticky top-0 z-40 border-b border-brand-border bg-white/95 backdrop-blur">
  <div class="mx-auto flex max-w-[1800px] items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
    <a href="<?= base_url('/') ?>" class="flex shrink-0 items-center">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Habitou Imóveis" class="h-9 w-auto">
    </a>

    <div class="hidden flex-1 items-center justify-center gap-3 lg:flex">
      <div class="airbnb-bar">
        <div class="cidade-pill" id="cidade-pill">
          <button type="button" class="cidade-pill-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <span class="cidade-pill-label">Cidade</span>
          </button>
          <div class="cidade-dropdown">
            <input type="text" class="cidade-busca-input" placeholder="Digite o nome da cidade..." autocomplete="off">
            <div class="cidade-sugestoes"></div>
          </div>
        </div>

        <span class="airbnb-bar-divider"></span>

        <a href="#" class="sp-seg on" data-transacao="">Todos</a>
        <a href="#" class="sp-seg" data-transacao="comprar">Comprar</a>
        <a href="#" class="sp-seg" data-transacao="alugar">Alugar</a>

        <button type="button" class="airbnb-bar-btn" id="airbnb-bar-search-btn" aria-label="Buscar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        </button>
      </div>

      <a href="<?= base_url('imoveis.php') ?>" class="filtros-pill-btn" id="filtros-toggle-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        <span>Filtros</span>
      </a>
    </div>

    <div class="hidden items-center gap-3 lg:flex">
      <a href="<?= base_url('quem-somos.php') ?>" class="text-sm font-medium hover:text-brand-primary">Quem somos</a>
      <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full bg-brand-primary px-4 py-2 text-sm font-semibold text-white hover:bg-brand-primary-hover">Anunciar imóvel</a>
      <?php if ($__user): ?>
        <div class="relative">
          <button type="button" class="nav-user-btn" id="nav-user-btn">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-primary text-xs font-semibold text-white"><?= e(mb_strtoupper(mb_substr($__user['first_name'], 0, 1))) ?></span>
            <span class="pr-2 text-sm font-medium"><?= e($__user['first_name']) ?></span>
          </button>
          <div class="nav-user-menu" id="nav-user-menu">
            <a href="<?= base_url('minha-conta.php') ?>">Minha conta</a>
            <a href="<?= base_url('anunciante/imoveis.php') ?>">Meus anúncios</a>
            <a href="<?= base_url('minha-conta-favoritos.php') ?>">Favoritos</a>
            <?php if (in_array($__user['role'], ['AGENCY_ADMIN', 'AGENT'], true)): ?>
              <a href="<?= base_url('imobiliaria/feeds.php') ?>">Painel da imobiliária</a>
            <?php endif; ?>
            <?php if ($__user['role'] === 'ADMIN'): ?>
              <a href="<?= base_url('admin/index.php') ?>">Administração</a>
            <?php endif; ?>
            <a href="<?= base_url('logout.php') ?>" class="mt-1 border-t border-brand-border pt-2 text-brand-text-secondary">Sair</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= base_url('login.php') ?>" class="rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary hover:text-brand-primary">Entrar</a>
        <a href="<?= base_url('cadastro.php') ?>" class="text-sm font-medium hover:text-brand-primary">Cadastre-se</a>
      <?php endif; ?>
    </div>

    <a href="<?= base_url('imoveis.php') ?>" class="filtros-pill-btn lg:!hidden" id="filtros-toggle-btn-mobile">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
      <span>Filtros</span>
    </a>
    <button id="mobile-menu-btn" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-brand-border lg:hidden" aria-label="Abrir menu">
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
<main class="flex-1">
