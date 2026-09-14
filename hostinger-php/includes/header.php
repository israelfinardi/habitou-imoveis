<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/auth_modal.php';
$__user = current_user();
$__pageTitle = $pageTitle ?? APP_NAME;
$__pageDescription = $pageDescription ?? 'Encontre apartamentos, casas e terrenos para comprar ou alugar em todo o Brasil.';
$__fullTitle = $__pageTitle . (!str_starts_with($__pageTitle, APP_NAME) ? ' | ' . APP_NAME : '');

// Open Graph / Twitter Card — imagem de capa quando o link é compartilhado
// (WhatsApp, Facebook, Instagram, Slack etc.). Cada página pode definir
// $ogImage (URL absoluta) antes de dar require neste arquivo — ex.: a foto
// principal do imóvel, o logo da imobiliária, o avatar do corretor, a
// hero_image_url da cidade. Sem isso, cai na capa padrão da marca.
$__ogImage = !empty($ogImage) ? $ogImage : base_url('assets/img/og-default.png');
$__ogUrl = $canonical ?? (base_url(ltrim($_SERVER['REQUEST_URI'] ?? '', '/')));

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
<title><?= e($__fullTitle) ?></title>
<meta name="description" content="<?= e($__pageDescription) ?>">
<?php if (!empty($canonical)): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<link rel="icon" href="<?= base_url('assets/img/favicon.ico') ?>">
<meta property="og:site_name" content="<?= e(APP_NAME) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<meta property="og:url" content="<?= e($__ogUrl) ?>">
<meta property="og:title" content="<?= e($__fullTitle) ?>">
<meta property="og:description" content="<?= e($__pageDescription) ?>">
<meta property="og:image" content="<?= e($__ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($__fullTitle) ?>">
<meta name="twitter:description" content="<?= e($__pageDescription) ?>">
<meta name="twitter:image" content="<?= e($__ogImage) ?>">
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
          sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
        },
      },
    },
  };
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',Arial,sans-serif}
.price-pin{background:transparent;border:0}
.price-pin-label{
  display:inline-block;white-space:nowrap;transform:translate(-50%,-100%);
  background:#fff;color:#222222;border:1px solid #DDDDDD;border-radius:9999px;
  padding:5px 11px;font-size:12px;font-weight:700;box-shadow:0 1px 5px rgba(0,0,0,.18);
  cursor:pointer;
}
.price-pin-label.active,.price-pin-label:hover{background:#C1502E;color:#fff;border-color:#C1502E;z-index:1000!important}
.js-home-carousel-track::-webkit-scrollbar{display:none}
/* Isola o Leaflet num novo stacking context — os z-index internos dele (até
   1000, nos controles de zoom) senão competem direto no stacking context
   raiz contra os dropdowns do header (.cidade-dropdown z-index:220,
   .nav-user-menu z-index:200) e vencem, cobrindo-os quando abertos. */
#home-minimap{position:relative;z-index:0;isolation:isolate}
.map-pin-popup .leaflet-popup-content-wrapper{padding:0;border-radius:20px;overflow:hidden;box-shadow:0 12px 28px rgba(0,0,0,.18),0 2px 6px rgba(0,0,0,.08)}
.map-pin-popup .leaflet-popup-content{margin:0;width:100%!important}
.map-pin-popup .leaflet-popup-tip{box-shadow:0 3px 6px rgba(0,0,0,.1)}
.map-pin-popup .leaflet-popup-close-button{
  top:10px!important;right:10px!important;width:28px!important;height:28px!important;
  display:flex!important;align-items:center;justify-content:center;
  background:#fff!important;border-radius:999px;
  box-shadow:0 1px 4px rgba(0,0,0,.3);font-size:16px!important;
  color:#222!important;z-index:20;
}

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
.nav-user-menu{position:absolute;top:calc(100% + 12px);right:0;background:#fff;border:1px solid #EBEBEB;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,.18);min-width:260px;padding:8px;display:none;z-index:200}
.nav-user-menu.on{display:block}
.nav-user-menu a{display:flex;align-items:center;gap:14px;padding:11px 14px;border-radius:10px;font-size:14.5px;font-weight:500;color:#222222;text-decoration:none}
.nav-user-menu a svg{flex:none;width:20px;height:20px;color:#222222}
.nav-user-menu a:hover{background:#F7F7F7}
.nav-user-menu-divider{margin:8px 4px;border-top:1px solid #EBEBEB}
.scrollbar-none{scrollbar-width:none}
.scrollbar-none::-webkit-scrollbar{display:none}
.hero-transacao-tab{display:inline-flex;flex:1}
.hero-transacao-tab input{position:absolute;opacity:0;width:0;height:0}
.hero-transacao-tab span{display:block;width:100%;text-align:center;border-radius:10px;padding:8px 12px;font-size:13.5px;font-weight:600;color:#717171;cursor:pointer;transition:.15s}
.hero-transacao-tab input:checked + span{background:#F7F7F7;color:#222222}

/* --- Navegação mobile estilo Airbnb: header só com a logo (o resto vira a
   barra inferior fixa, includes/bottom_nav.php) — ambos escondem/mostram
   ao rolar a tela (assets/js/bottom-nav.js). --- */
:root{--hb-bn-h:64px}
@media (min-width:1024px){:root{--hb-bn-h:0px}}
header{transition:transform .25s ease}
header.hb-hidden{transform:translateY(-100%)}
@media (min-width:1024px){header.hb-hidden{transform:none}}
#hb-bottom-nav{position:fixed;left:0;right:0;bottom:0;z-index:50;display:flex;align-items:stretch;background:#fff;border-top:1px solid #DDDDDD;box-shadow:0 -2px 10px rgba(0,0,0,.06);padding-bottom:env(safe-area-inset-bottom);transition:transform .25s ease}
#hb-bottom-nav.hb-hidden{transform:translateY(100%)}
.hb-bn-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:8px 4px 6px;font-size:11px;font-weight:600;color:#717171;background:0 0;border:0;cursor:pointer;text-decoration:none;-webkit-tap-highlight-color:transparent}
.hb-bn-item svg{width:24px;height:24px}
.hb-bn-item.is-active{color:#C1502E}
.hb-bn-avatar{width:24px;height:24px;border-radius:50%;object-fit:cover}
/* Barras fixas inferiores de outras páginas (CTA de contato do imóvel,
   barra de comparação) sobem pra cima da barra de navegação no mobile, em
   vez de sobrepor os botões dela. */
.hb-fixed-bottom-bar{bottom:0}
@media (max-width:1023.98px){
  .hb-fixed-bottom-bar{bottom:var(--hb-bn-h)}
  body{padding-bottom:var(--hb-bn-h)}
}
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
      <a href="<?= base_url('anunciante/novo.php') ?>" class="rounded-full border border-brand-text/30 px-4 py-2 text-sm font-semibold text-brand-text hover:border-brand-text">Anunciar imóvel</a>
      <?php if ($__user): ?>
        <div class="relative">
          <button type="button" class="nav-user-btn" id="nav-user-btn">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-primary text-xs font-semibold text-white">
              <?php if (!empty($__user['avatar_url'])): ?>
                <img src="<?= e($__user['avatar_url']) ?>" class="h-full w-full object-cover" alt="">
              <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($__user['first_name'], 0, 1))) ?>
              <?php endif; ?>
            </span>
            <span class="pr-2 text-sm font-medium"><?= e($__user['first_name']) ?></span>
          </button>
          <?php
          $__navItems = account_nav_items();
          $__navPrimary = array_intersect_key($__navItems, array_flip(['overview', 'anuncios', 'favoritos']));
          $__navSecondary = array_diff_key($__navItems, $__navPrimary);
          ?>
          <div class="nav-user-menu" id="nav-user-menu">
            <?php foreach ($__navPrimary as [$href, $label, $icon]): ?>
              <a href="<?= base_url($href) ?>"><?= render_nav_icon($icon) ?><?= e($label) ?></a>
            <?php endforeach; ?>
            <?php if ($__navSecondary): ?>
              <div class="nav-user-menu-divider"></div>
              <?php foreach ($__navSecondary as [$href, $label, $icon]): ?>
                <a href="<?= base_url($href) ?>"><?= render_nav_icon($icon) ?><?= e($label) ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($__user['role'] === 'ADMIN'): ?>
              <div class="nav-user-menu-divider"></div>
              <?php foreach (admin_nav_items() as [$href, $label, $icon]): ?>
                <a href="<?= base_url($href) ?>"><?= render_nav_icon($icon) ?><?= e($label) ?></a>
              <?php endforeach; ?>
            <?php endif; ?>
            <div class="nav-user-menu-divider"></div>
            <a href="<?= base_url('logout.php') ?>">Sair</a>
          </div>
        </div>
      <?php else: ?>
        <button type="button" data-open-auth class="rounded-full border border-brand-border px-4 py-2 text-sm font-medium hover:border-brand-primary hover:text-brand-primary">Entrar ou cadastrar-se</button>
      <?php endif; ?>
    </div>

  </div>
</header>
<?php if (!$__user) { render_auth_modal(); } ?>
<main class="flex-1">
