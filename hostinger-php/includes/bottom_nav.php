<?php
/**
 * Barra de navegação inferior fixa, só no mobile (estilo Airbnb: Buscar /
 * Favoritos / Entrar-Perfil) — substitui o antigo menu hambúrguer do header
 * (includes/header.php), que no mobile agora mostra só a logo. Esconde/
 * mostra ao rolar a tela (assets/js/bottom-nav.js); na página de
 * resultados/mapa, quem manda é o estado da bandeja arrastável (recolhida
 * esconde, metade/cheia mostra — ver assets/js/results-map.js).
 */
function render_mobile_bottom_nav(): void
{
    $user = current_user();
    $current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $onBuscar = in_array($current, ['imoveis.php', 'index.php'], true);
    $onFavoritos = $current === 'minha-conta-favoritos.php';
    $onConta = $user && str_starts_with($current, 'minha-conta') && !$onFavoritos;
    ?>
    <nav id="hb-bottom-nav" class="lg:hidden" aria-label="Navegação principal">
      <a href="<?= base_url('imoveis.php') ?>" class="hb-bn-item<?= $onBuscar ? ' is-active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
        <span>Buscar</span>
      </a>
      <a href="<?= base_url('minha-conta-favoritos.php') ?>" class="hb-bn-item<?= $onFavoritos ? ' is-active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-10-9.3C.4 8.1 2 4.5 5.6 4c2-.3 3.8.6 6.4 3 2.6-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.1 3.6 7.7C19.5 16.4 12 21 12 21z"/></svg>
        <span>Favoritos</span>
      </a>
      <?php if ($user): ?>
        <a href="<?= base_url('minha-conta.php') ?>" class="hb-bn-item<?= $onConta ? ' is-active' : '' ?>">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= e($user['avatar_url']) ?>" class="hb-bn-avatar" alt="">
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4.5"/></svg>
          <?php endif; ?>
          <span>Perfil</span>
        </a>
      <?php else: ?>
        <button type="button" data-open-auth class="hb-bn-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4.5"/></svg>
          <span>Entrar</span>
        </button>
      <?php endif; ?>
    </nav>
    <?php
}
